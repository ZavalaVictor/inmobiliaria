<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoCorreo;
use App\Enums\TipoCorreo;
use App\Jobs\Citas\SendCitaCorreoJob;
use App\Mail\Citas\CitaMail;
use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\HistorialCorreo;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use App\Services\Citas\CitaCorreoSender;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class CitaNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Mail::fake();
    }

    public function test_creation_notifies_client_and_agent_and_records_successful_emails(): void
    {
        $admin = $this->user('cita-event-admin@example.test', 'Administrador');
        $clientUser = $this->user('cita-client@example.test', 'Cliente');
        $agentUser = $this->agentUser('cita-agent@example.test');
        [$client, $property, $agent] = $this->bookableSet('creation', $clientUser, $agentUser);

        $response = $this->apiPost('/api/v1/citas', $admin, [
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-10 10:00:00',
            'fecha_fin' => '2026-10-10 11:00:00',
        ])->assertCreated();

        $citaId = $response->json('data.id');
        self::assertSame(2, DB::table('notifications')->count());
        self::assertSame(
            ['cita_confirmada', 'cita_confirmada'],
            DB::table('notifications')->orderBy('id')->get()->map(
                fn (object $notification): string => json_decode($notification->data, true)['tipo'],
            )->all(),
        );
        self::assertEqualsCanonicalizing(
            [$clientUser->id, $agentUser->id],
            DB::table('notifications')->pluck('notifiable_id')->all(),
        );
        self::assertDatabaseCount('historial_correos', 2);
        self::assertDatabaseHas('historial_correos', [
            'cita_id' => $citaId,
            'tipo' => TipoCorreo::ConfirmacionCita->value,
            'estado' => EstadoCorreo::Enviado->value,
        ]);
        self::assertSame(2, HistorialCorreo::query()->whereNotNull('fecha_envio')->count());
        Mail::assertSent(CitaMail::class, 2);
    }

    public function test_client_without_user_still_receives_email_without_internal_notification(): void
    {
        $admin = $this->user('cita-no-user-admin@example.test', 'Administrador');
        $agentUser = $this->agentUser('cita-no-user-agent@example.test');
        $client = $this->client('Cliente sin usuario');
        [$property, $agent] = $this->assignedPropertyAndAgent('no-user', $client, $agentUser);

        $this->apiPost('/api/v1/citas', $admin, [
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-11 10:00:00',
            'fecha_fin' => '2026-10-11 11:00:00',
        ])->assertCreated();

        self::assertSame(1, DB::table('notifications')->count());
        self::assertDatabaseHas('historial_correos', [
            'cliente_id' => $client->id,
            'destinatario_user_id' => null,
            'destinatario_email' => $client->email,
            'estado' => EstadoCorreo::Enviado->value,
        ]);
        self::assertSame(2, HistorialCorreo::query()->count());
        Mail::assertSent(CitaMail::class, 2);
    }

    public function test_same_user_as_client_and_agent_is_notified_once_per_channel(): void
    {
        $admin = $this->user('cita-dedup-admin@example.test', 'Administrador');
        $person = $this->user('cita-dedup-person@example.test', 'Cliente');
        $person->assignRole('Agente Inmobiliario');
        $agentUser = $person->fresh();
        $sequence = ++self::$sequence;
        $agent = Agente::create([
            'user_id' => $person->id,
            'numero_empleado' => 'CIT-'.$sequence,
        ]);
        $client = $this->client('Cliente duplicado', $person);
        $client->update(['email' => $person->email]);
        [$property] = $this->assignedPropertyAndAgent('dedup', $client, $agentUser, $agent);

        $this->apiPost('/api/v1/citas', $admin, [
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-12 10:00:00',
            'fecha_fin' => '2026-10-12 11:00:00',
        ])->assertCreated();

        self::assertSame(1, DB::table('notifications')->count());
        self::assertSame(1, HistorialCorreo::query()->count());
        Mail::assertSent(CitaMail::class, 1);
    }

    public function test_rescheduling_notifies_both_recipients_and_preserves_history(): void
    {
        $admin = $this->user('cita-reschedule-admin@example.test', 'Administrador');
        $clientUser = $this->user('cita-reschedule-client@example.test', 'Cliente');
        $agentUser = $this->agentUser('cita-reschedule-agent@example.test');
        [$client, $property, $agent] = $this->bookableSet('reschedule', $clientUser, $agentUser);
        $cita = $this->createAppointment($admin, $client, $property, $agent);

        $this->apiPatch('/api/v1/citas/'.$cita->id.'/reprogramar', $admin, [
            'fecha_inicio' => '2026-10-13 12:00:00',
            'fecha_fin' => '2026-10-13 13:00:00',
            'motivo' => 'El cliente solicitó otro horario',
        ])->assertOk();

        self::assertDatabaseHas('cita_historial', [
            'cita_id' => $cita->id,
            'fecha_inicio_anterior' => '2026-10-01 10:00:00',
            'fecha_fin_anterior' => '2026-10-01 11:00:00',
            'fecha_inicio_nueva' => '2026-10-13 12:00:00',
            'fecha_fin_nueva' => '2026-10-13 13:00:00',
            'motivo' => 'El cliente solicitó otro horario',
            'tipo_cambio' => 'reprogramacion',
        ]);
        self::assertSame(2, DB::table('notifications')->whereJsonContains('data->tipo', 'cita_reprogramada')->count());
        self::assertSame(2, HistorialCorreo::query()->where('tipo', TipoCorreo::ReprogramacionCita->value)->count());

        $notification = DB::table('notifications')->whereJsonContains('data->tipo', 'cita_reprogramada')->first();
        $payload = json_decode($notification->data, true);
        self::assertSame(['tipo' => 'cita', 'id' => $cita->id], $payload['entidad']);
        self::assertStringContainsString('2026-10-01 10:00', $payload['mensaje']);
        self::assertStringContainsString('2026-10-13 12:00', $payload['mensaje']);
        Mail::assertSent(CitaMail::class, 2);

        $historyCount = $cita->fresh()->historial()->count();
        $notificationCount = DB::table('notifications')->count();
        $mailHistoryCount = HistorialCorreo::query()->count();

        $this->apiPatch('/api/v1/citas/'.$cita->id.'/reprogramar', $admin, [
            'fecha_inicio' => '2026-10-13 12:00:00',
            'fecha_fin' => '2026-10-13 13:00:00',
            'motivo' => 'Reintento idempotente',
        ])->assertOk();

        self::assertSame($historyCount, $cita->fresh()->historial()->count());
        self::assertSame($notificationCount, DB::table('notifications')->count());
        self::assertSame($mailHistoryCount, HistorialCorreo::query()->count());
    }

    public function test_only_a_real_transition_to_cancelled_notifies_recipients(): void
    {
        $admin = $this->user('cita-cancel-admin@example.test', 'Administrador');
        $clientUser = $this->user('cita-cancel-client@example.test', 'Cliente');
        $agentUser = $this->agentUser('cita-cancel-agent@example.test');
        [$client, $property, $agent] = $this->bookableSet('cancel', $clientUser, $agentUser);
        $cita = $this->createAppointment($admin, $client, $property, $agent);

        $this->apiPatch('/api/v1/citas/'.$cita->id.'/estado', $admin, ['estado' => 'confirmada'])->assertOk();
        self::assertSame(0, DB::table('notifications')->count());

        $this->apiPatch('/api/v1/citas/'.$cita->id.'/estado', $admin, ['estado' => 'cancelada'])->assertOk();
        self::assertSame(2, DB::table('notifications')->whereJsonContains('data->tipo', 'cita_cancelada')->count());
        self::assertSame(2, HistorialCorreo::query()->where('tipo', TipoCorreo::CancelacionCita->value)->count());

        $this->apiPatch('/api/v1/citas/'.$cita->id.'/estado', $admin, ['estado' => 'cancelada'])->assertOk();
        self::assertSame(2, DB::table('notifications')->whereJsonContains('data->tipo', 'cita_cancelada')->count());
        self::assertSame(2, HistorialCorreo::query()->where('tipo', TipoCorreo::CancelacionCita->value)->count());
    }

    public function test_email_history_is_pending_before_job_and_becomes_sent(): void
    {
        Queue::fake();
        $admin = $this->user('cita-pending-admin@example.test', 'Administrador');
        $clientUser = $this->user('cita-pending-client@example.test', 'Cliente');
        $agentUser = $this->agentUser('cita-pending-agent@example.test');
        [$client, $property, $agent] = $this->bookableSet('pending', $clientUser, $agentUser);

        $this->apiPost('/api/v1/citas', $admin, [
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-14 10:00:00',
            'fecha_fin' => '2026-10-14 11:00:00',
        ])->assertCreated();

        self::assertSame(2, HistorialCorreo::query()->where('estado', EstadoCorreo::Pendiente->value)->count());
        Queue::assertPushed(SendCitaCorreoJob::class, 2);

        foreach (Queue::pushed(SendCitaCorreoJob::class) as $job) {
            $job->handle(app(CitaCorreoSender::class));
        }

        self::assertSame(2, HistorialCorreo::query()->where('estado', EstadoCorreo::Enviado->value)->count());
        self::assertSame(2, HistorialCorreo::query()->whereNotNull('fecha_envio')->count());
        Mail::assertSent(CitaMail::class, 2);
    }

    public function test_email_failure_is_sanitized_and_does_not_rollback_cita(): void
    {
        Queue::fake();
        $admin = $this->user('cita-failure-admin@example.test', 'Administrador');
        $clientUser = $this->user('cita-failure-client@example.test', 'Cliente');
        $agentUser = $this->agentUser('cita-failure-agent@example.test');
        [$client, $property, $agent] = $this->bookableSet('failure', $clientUser, $agentUser);

        $response = $this->apiPost('/api/v1/citas', $admin, [
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-15 10:00:00',
            'fecha_fin' => '2026-10-15 11:00:00',
        ])->assertCreated();
        $citaId = $response->json('data.id');
        $history = HistorialCorreo::query()->firstOrFail();
        $sender = Mockery::mock(CitaCorreoSender::class);
        $sender->shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP password=secret token=abc'));

        (new SendCitaCorreoJob($history->id, [
            'tipo' => TipoCorreo::ConfirmacionCita->value,
            'asunto' => 'Confirmación de cita',
            'plantilla' => 'emails.citas.confirmacion',
            'destinatario_nombre' => 'Cliente',
            'cita_id' => $citaId,
            'fecha_inicio' => '2026-10-15 10:00',
            'fecha_fin' => '2026-10-15 11:00',
        ]))->handle($sender);

        $history->refresh();
        self::assertSame(EstadoCorreo::Fallido, $history->estado);
        self::assertNotNull($history->mensaje_error);
        self::assertStringNotContainsString('secret', $history->mensaje_error);
        self::assertStringNotContainsString('token=abc', $history->mensaje_error);
        self::assertDatabaseHas('citas', ['id' => $citaId, 'estado' => 'programada']);
    }

    public function test_sync_mail_failure_keeps_creation_successful_and_marks_history_failed(): void
    {
        $sender = Mockery::mock(CitaCorreoSender::class);
        $sender->shouldReceive('send')->twice()->andThrow(new \RuntimeException('SMTP password=secret token=abc'));
        app()->instance(CitaCorreoSender::class, $sender);

        $admin = $this->user('cita-sync-failure-admin@example.test', 'Administrador');
        $clientUser = $this->user('cita-sync-failure-client@example.test', 'Cliente');
        $agentUser = $this->agentUser('cita-sync-failure-agent@example.test');
        [$client, $property, $agent] = $this->bookableSet('sync-failure', $clientUser, $agentUser);

        $response = $this->apiPost('/api/v1/citas', $admin, [
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-16 10:00:00',
            'fecha_fin' => '2026-10-16 11:00:00',
        ])->assertCreated();

        self::assertDatabaseHas('citas', [
            'id' => $response->json('data.id'),
            'estado' => 'programada',
        ]);
        self::assertSame(2, HistorialCorreo::query()->where('estado', EstadoCorreo::Fallido->value)->count());
        self::assertSame(2, HistorialCorreo::query()->whereNotNull('mensaje_error')->count());
        self::assertStringNotContainsString('secret', (string) HistorialCorreo::query()->value('mensaje_error'));
        self::assertStringNotContainsString('token=abc', (string) HistorialCorreo::query()->value('mensaje_error'));
    }

    private function bookableSet(string $suffix, User $clientUser, User $agentUser): array
    {
        $client = $this->client('Cliente '.$suffix, $clientUser);
        [$property, $agent] = $this->assignedPropertyAndAgent($suffix, $client, $agentUser);

        return [$client, $property, $agent];
    }

    private function assignedPropertyAndAgent(string $suffix, Cliente $client, User $agentUser, ?Agente $agent = null): array
    {
        $agent ??= $agentUser->agente;
        $property = $this->property('CITA-'.strtoupper($suffix));
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->id]);
        AgenteInmueble::create(['inmueble_id' => $property->id, 'agente_id' => $agent->id]);

        return [$property, $agent];
    }

    private function createAppointment(User $creator, Cliente $client, Inmueble $property, Agente $agent): Cita
    {
        return Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $creator->id,
            'fecha_inicio' => '2026-10-01 10:00:00',
            'fecha_fin' => '2026-10-01 11:00:00',
            'estado' => 'programada',
        ]);
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Citas',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function agentUser(string $email): User
    {
        $user = $this->user($email, 'Agente Inmobiliario');
        $sequence = ++self::$sequence;
        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => 'CIT-'.$sequence,
        ]);

        return $user->fresh('agente');
    }

    private function client(string $name, ?User $user = null): Cliente
    {
        return Cliente::create([
            'user_id' => $user?->id,
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => 'client-'.(++self::$sequence).'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function property(string $code): Inmueble
    {
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario '.$code,
            'rfc' => 'RFC'.str_pad((string) (++self::$sequence), 10, '0', STR_PAD_LEFT),
            'telefono' => '5555555555',
            'direccion' => 'Dirección '.$code,
        ]);
        $category = Categoria::create(['nombre' => 'Categoría '.$code]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => $code,
            'titulo' => 'Inmueble '.$code,
            'slug' => strtolower($code).'-slug',
            'tipo_operacion' => 'venta',
            'precio_venta' => 100000,
            'calle' => 'Calle '.$code,
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '01000',
        ]);
    }

    private function apiPost(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson($uri, $payload);
    }

    private function apiPatch(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson($uri, $payload);
    }
}
