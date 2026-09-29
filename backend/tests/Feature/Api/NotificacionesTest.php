<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_notification_endpoints_require_authentication(): void
    {
        $id = (string) Str::uuid();

        $this->getJson('/api/v1/notificaciones')->assertUnauthorized();
        $this->getJson('/api/v1/notificaciones/no-leidas/count')->assertUnauthorized();
        $this->patchJson('/api/v1/notificaciones/'.$id.'/leer')->assertUnauthorized();
        $this->patchJson('/api/v1/notificaciones/leer-todas')->assertUnauthorized();
    }

    public function test_user_only_sees_own_notifications_in_descending_order(): void
    {
        $user = $this->user('notifications-owner@example.test', 'Administrador');
        $other = $this->user('notifications-other@example.test', 'Cliente');

        $older = $this->notification($user, 'older', '2026-09-28 10:00:00');
        $newer = $this->notification($user, 'newer', '2026-09-29 10:00:00');
        $foreign = $this->notification($other, 'foreign', '2026-09-30 10:00:00');

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/v1/notificaciones?user_id='.$other->id.'&notifiable_id='.$other->id);

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['id' => $foreign->id]);
    }

    public function test_index_filters_read_and_unread_notifications_and_paginates(): void
    {
        $user = $this->user('notifications-filters@example.test', 'Cliente');
        $unread = $this->notification($user, 'unread', '2026-09-29 12:00:00');
        $read = $this->notification($user, 'read', '2026-09-29 11:00:00', true);
        $this->notification($user, 'third', '2026-09-29 10:00:00');

        $base = '/api/v1/notificaciones';

        $this->actingAs($user, 'web')
            ->getJson($base.'?estado=no_leida')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $unread->id])
            ->assertJsonMissing(['id' => $read->id]);

        $this->actingAs($user, 'web')
            ->getJson($base.'?estado=leida&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.id', $read->id);

        $this->actingAs($user, 'web')
            ->getJson($base.'?estado=otro')
            ->assertUnprocessable();
    }

    public function test_unread_count_only_counts_current_users_notifications(): void
    {
        $user = $this->user('notifications-count@example.test', 'Administrador');
        $other = $this->user('notifications-count-other@example.test', 'Agente Inmobiliario');

        $this->notification($user, 'unread');
        $this->notification($user, 'read', null, true);
        $this->notification($other, 'foreign');

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/notificaciones/no-leidas/count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);
    }

    public function test_user_can_mark_own_notification_as_read_idempotently(): void
    {
        $user = $this->user('notifications-read@example.test', 'Agente Inmobiliario');
        $notification = $this->notification($user, 'readable');

        $first = $this->actingAs($user, 'web')
            ->patchJson('/api/v1/notificaciones/'.$notification->id.'/leer');

        $first->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.leida', true);
        self::assertNotNull($notification->fresh()->read_at);

        $readAt = $notification->fresh()->read_at;
        $this->actingAs($user, 'web')
            ->patchJson('/api/v1/notificaciones/'.$notification->id.'/leer')
            ->assertOk()
            ->assertJsonPath('data.leida', true);

        self::assertEquals($readAt?->toISOString(), $notification->fresh()->read_at?->toISOString());
    }

    public function test_user_cannot_mark_foreign_or_missing_notification_as_read(): void
    {
        $user = $this->user('notifications-read-owner@example.test', 'Cliente');
        $other = $this->user('notifications-read-foreign@example.test', 'Administrador');
        $foreign = $this->notification($other, 'foreign');

        $this->actingAs($user, 'web')
            ->patchJson('/api/v1/notificaciones/'.$foreign->id.'/leer')
            ->assertNotFound();

        $this->actingAs($user, 'web')
            ->patchJson('/api/v1/notificaciones/'.Str::uuid().'/leer')
            ->assertNotFound();
    }

    public function test_user_can_mark_all_own_notifications_as_read_without_touching_others(): void
    {
        $user = $this->user('notifications-all@example.test', 'Administrador');
        $other = $this->user('notifications-all-other@example.test', 'Cliente');
        $ownOne = $this->notification($user, 'one');
        $ownTwo = $this->notification($user, 'two');
        $foreign = $this->notification($other, 'foreign');

        $this->actingAs($user, 'web')
            ->patchJson('/api/v1/notificaciones/leer-todas')
            ->assertOk()
            ->assertJsonPath('data.updated', 2);

        self::assertNotNull($ownOne->fresh()->read_at);
        self::assertNotNull($ownTwo->fresh()->read_at);
        self::assertNull($foreign->fresh()->read_at);

        $this->actingAs($user, 'web')
            ->patchJson('/api/v1/notificaciones/leer-todas')
            ->assertOk()
            ->assertJsonPath('data.updated', 0);
    }

    public function test_admin_agent_and_client_can_read_only_their_own_notifications(): void
    {
        foreach (['Administrador', 'Agente Inmobiliario', 'Cliente'] as $index => $role) {
            $user = $this->user('notifications-role-'.$index.'@example.test', $role);
            $other = $this->user('notifications-role-other-'.$index.'@example.test', 'Cliente');
            $own = $this->notification($user, 'own');
            $foreign = $this->notification($other, 'foreign');

            Auth::forgetGuards();
            $this->actingAs($user, 'web')
                ->getJson('/api/v1/notificaciones')
                ->assertOk()
                ->assertJsonFragment(['id' => $own->id])
                ->assertJsonMissing(['id' => $foreign->id]);
        }
    }

    public function test_resource_exposes_controlled_payload_without_notification_ownership_fields(): void
    {
        $user = $this->user('notifications-payload@example.test', 'Cliente');
        $notification = $this->notification($user, 'cita_reprogramada', null, false, [
            'tipo' => 'cita_reprogramada',
            'titulo' => 'Cita reprogramada',
            'mensaje' => 'La cita cambió de horario.',
            'url' => '/portal-cliente/citas/42',
            'entidad' => [
                'tipo' => 'cita',
                'id' => 42,
                'interno' => 'no debe exponerse',
            ],
            'password' => 'no debe exponerse',
        ]);

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonPath('data.0.tipo', 'cita_reprogramada')
            ->assertJsonPath('data.0.titulo', 'Cita reprogramada')
            ->assertJsonPath('data.0.mensaje', 'La cita cambió de horario.')
            ->assertJsonPath('data.0.url', '/portal-cliente/citas/42')
            ->assertJsonPath('data.0.entidad.tipo', 'cita')
            ->assertJsonPath('data.0.entidad.id', 42)
            ->assertJsonMissingPath('data.0.entidad.interno')
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.notifiable_type')
            ->assertJsonMissingPath('data.0.notifiable_id');

        self::assertSame($notification->id, $notification->fresh()->id);
    }

    public function test_resource_handles_incomplete_payload_without_failing(): void
    {
        $user = $this->user('notifications-minimal@example.test', 'Cliente');
        $notification = $this->notification($user, 'minimal', null, false, [
            'tipo' => 'minimal',
            'titulo' => 'Título mínimo',
            'mensaje' => 'Mensaje mínimo',
        ]);

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.url', null)
            ->assertJsonPath('data.0.entidad', null);
    }

    public function test_notification_write_endpoint_is_not_exposed(): void
    {
        $user = $this->user('notifications-no-write@example.test', 'Administrador');

        $this->actingAs($user, 'web')
            ->postJson('/api/v1/notificaciones', [])
            ->assertMethodNotAllowed();
    }

    private function notification(
        User $user,
        string $type,
        ?string $createdAt = null,
        bool $read = false,
        ?array $data = null,
    ): DatabaseNotification {
        $timestamp = $createdAt ?? now()->toDateTimeString();

        return DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'tests.notifications.'.$type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->getKey(),
            'data' => $data ?? [
                'tipo' => $type,
                'titulo' => 'Título '.$type,
                'mensaje' => 'Mensaje '.$type,
                'url' => null,
                'entidad' => null,
            ],
            'read_at' => $read ? $timestamp : null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Notificaciones',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }
}
