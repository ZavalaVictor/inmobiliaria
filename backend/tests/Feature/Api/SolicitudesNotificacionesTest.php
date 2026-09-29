<?php

namespace Tests\Feature\Api;

use App\Models\SolicitudInformacion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SolicitudesNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_null_to_user_notifies_only_the_new_responsible_and_appears_in_inbox(): void
    {
        $admin = $this->user('solicitud-assignment-admin@example.test', 'Administrador');
        $responsable = $this->user('solicitud-assignment-responsable@example.test', 'Asistente');
        $other = $this->user('solicitud-assignment-other@example.test', 'Asistente');
        $solicitud = $this->solicitud();

        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
            'atendida_por_user_id' => $responsable->id,
        ])->assertOk();

        self::assertSame(1, DB::table('notifications')->count());
        self::assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $responsable->id,
        ]);

        $this->apiGet('/api/v1/notificaciones', $responsable)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo', 'solicitud_asignada')
            ->assertJsonPath('data.0.titulo', 'Solicitud asignada')
            ->assertJsonPath('data.0.mensaje', 'Se te asignó una solicitud de información.')
            ->assertJsonPath('data.0.url', '/solicitudes/'.$solicitud->id)
            ->assertJsonPath('data.0.entidad.tipo', 'solicitud')
            ->assertJsonPath('data.0.entidad.id', $solicitud->id)
            ->assertJsonMissing(['email' => $solicitud->email])
            ->assertJsonMissing(['telefono' => $solicitud->telefono])
            ->assertJsonMissing(['mensaje' => $solicitud->mensaje]);

        $this->apiGet('/api/v1/notificaciones', $other)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_same_responsible_is_idempotent_and_a_new_responsible_alone_is_notified(): void
    {
        $admin = $this->user('solicitud-idempotent-admin@example.test', 'Administrador');
        $responsableA = $this->user('solicitud-responsable-a@example.test', 'Asistente');
        $responsableB = $this->user('solicitud-responsable-b@example.test', 'Asistente');
        $solicitud = $this->solicitud();

        $this->assign($admin, $solicitud, $responsableA);
        $this->assign($admin, $solicitud, $responsableA);
        self::assertSame(1, DB::table('notifications')->count());

        $this->assign($admin, $solicitud, $responsableB);

        self::assertSame(2, DB::table('notifications')->count());
        self::assertSame(1, DB::table('notifications')->where('notifiable_id', $responsableA->id)->count());
        self::assertSame(1, DB::table('notifications')->where('notifiable_id', $responsableB->id)->count());
    }

    public function test_unassigning_does_not_create_a_notification(): void
    {
        $admin = $this->user('solicitud-unassign-admin@example.test', 'Administrador');
        $responsable = $this->user('solicitud-unassign-responsable@example.test', 'Asistente');
        $solicitud = $this->solicitud();

        $this->assign($admin, $solicitud, $responsable);
        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
            'atendida_por_user_id' => null,
        ])->assertOk();

        self::assertSame(1, DB::table('notifications')->count());
        self::assertNull($solicitud->fresh()->atendida_por_user_id);
    }

    public function test_status_and_attention_date_changes_do_not_notify_assignment(): void
    {
        $admin = $this->user('solicitud-state-admin@example.test', 'Administrador');
        $solicitud = $this->solicitud();

        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
            'estado' => 'en_atencion',
        ])->assertOk();
        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
            'fecha_atencion' => '2026-09-29 10:00:00',
        ])->assertOk();

        self::assertSame(0, DB::table('notifications')->count());
    }

    public function test_public_creation_keeps_zero_notification_side_effects(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/public/solicitudes', [
                'nombre' => 'Visitante público',
                'email' => 'public-notification@example.test',
                'mensaje' => 'Solicitud pública',
            ])
            ->assertCreated();

        self::assertSame(0, DB::table('notifications')->count());
        self::assertSame(1, SolicitudInformacion::query()->count());
    }

    private function assign(User $admin, SolicitudInformacion $solicitud, User $responsable): void
    {
        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
            'atendida_por_user_id' => $responsable->id,
        ])->assertOk();
    }

    private function solicitud(): SolicitudInformacion
    {
        return SolicitudInformacion::create([
            'nombre' => 'Solicitud de prueba',
            'email' => 'solicitud@example.test',
            'telefono' => '5555555555',
            'mensaje' => 'Mensaje privado de prueba',
        ]);
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Solicitud',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function apiGet(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }

    private function apiPatch(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson($uri, $payload);
    }
}
