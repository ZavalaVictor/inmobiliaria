<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoUsuario;
use App\Models\Agente;
use App\Models\Categoria;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\ClienteInmuebleInteres;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PortalClienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_portal_requires_a_valid_linked_client_user_and_rejects_other_roles(): void
    {
        $orphan = $this->user('portal-orphan@example.test', 'Cliente');
        $this->apiGet('/api/v1/portal-cliente', $orphan)->assertForbidden();

        foreach (['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General'] as $role) {
            $this->apiGet('/api/v1/portal-cliente', $this->user('portal-'.$role.'@example.test', $role))->assertForbidden();
        }

        Auth::forgetGuards();
        $this->withHeaders(['Accept' => 'application/json'])->getJson('/api/v1/portal-cliente')->assertUnauthorized();
    }

    public function test_summary_and_detail_endpoints_are_isolated_between_clients(): void
    {
        [$userA, $clientA, $propertyA, $appointmentA] = $this->clientFixture('a');
        [$userB, $clientB, $propertyB, $appointmentB] = $this->clientFixture('b');
        $this->notification($userA, 'notificacion_a');
        $this->notification($userB, 'notificacion_b');

        $summary = $this->apiGet('/api/v1/portal-cliente', $userA)->assertOk();
        $summary->assertJsonPath('data.cliente.id', $clientA->id)
            ->assertJsonPath('data.inmuebles_interes.total', 1)
            ->assertJsonPath('data.notificaciones.no_leidas', 1)
            ->assertJsonPath('data.inmuebles_interes.items.0.inmueble.id', $propertyA->id)
            ->assertJsonPath('data.citas.proximas.0.id', $appointmentA->id);

        $this->apiGet('/api/v1/portal-cliente/inmuebles-interes', $userA)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.inmueble.id', $propertyA->id)
            ->assertJsonPath('data.0.inmueble.precio', 1000000)
            ->assertJsonPath('data.0.inmueble.moneda', 'MXN')
            ->assertJsonMissingPath('data.0.inmueble.propietario_id')
            ->assertJsonMissingPath('data.0.inmueble.agentes');

        $this->apiGet('/api/v1/portal-cliente/citas?tipo=proximas', $userA)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $appointmentA->id)
            ->assertJsonMissingPath('data.0.motivo')
            ->assertJsonMissingPath('data.0.agente.numero_empleado');
    }

    public function test_portal_citas_separate_upcoming_and_history_and_limits_pagination(): void
    {
        [$user, $client, $property, $appointment] = $this->clientFixture('history');
        $appointment->update(['estado' => 'completada', 'fecha_inicio' => '2026-01-01 10:00:00', 'fecha_fin' => '2026-01-01 11:00:00']);
        Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => $appointment->agente_id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $appointment->creado_por_user_id,
            'fecha_inicio' => '2026-12-01 10:00:00',
            'fecha_fin' => '2026-12-01 11:00:00',
            'estado' => 'programada',
        ]);

        $this->apiGet('/api/v1/portal-cliente/citas?tipo=historial&per_page=1', $user)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estado', 'completada');
        $this->apiGet('/api/v1/portal-cliente/citas?tipo=proximas', $user)
            ->assertOk()
            ->assertJsonPath('data.0.estado', 'programada');
        $this->apiGet('/api/v1/portal-cliente/citas?tipo=invalid', $user)->assertUnprocessable();
    }

    public function test_inactive_client_user_is_blocked_and_data_is_preserved(): void
    {
        [$user, $client, $property, $appointment] = $this->clientFixture('inactive');
        $this->notification($user, 'persisted');
        $user->update(['estado' => EstadoUsuario::Inactivo]);

        $this->apiGet('/api/v1/portal-cliente', $user)->assertUnauthorized();
        self::assertDatabaseHas('clientes', ['id' => $client->id, 'user_id' => $user->id]);
        self::assertDatabaseHas('cliente_inmueble_intereses', ['cliente_id' => $client->id, 'inmueble_id' => $property->id]);
        self::assertDatabaseHas('citas', ['id' => $appointment->id, 'cliente_id' => $client->id]);
        self::assertSame(1, $user->notifications()->count());
    }

    public function test_portal_does_not_fallback_to_email_and_does_not_accept_scope_parameters(): void
    {
        $user = $this->user('email-only@example.test', 'Cliente');
        $client = $this->client('Sin vínculo', $user->email);

        $this->apiGet('/api/v1/portal-cliente?cliente_id='.$client->id, $user)->assertForbidden();
        self::assertNull($client->fresh()->user_id);
    }

    private function clientFixture(string $suffix): array
    {
        $user = $this->user('portal-'.$suffix.'@example.test', 'Cliente');
        $client = $this->client('Cliente '.strtoupper($suffix), $user->email, $user);
        $category = Categoria::create(['nombre' => 'Casa '.strtoupper($suffix), 'activo' => true]);
        $property = Inmueble::create([
            'propietario_id' => $this->owner()->id,
            'categoria_id' => $category->id,
            'codigo' => 'PORTAL-'.$suffix,
            'titulo' => 'Propiedad '.strtoupper($suffix),
            'slug' => 'propiedad-'.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '1000000.00',
            'calle' => 'Calle Portal',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '01000',
            'estado_disponibilidad' => 'disponible',
            'publicado' => true,
        ]);
        ClienteInmuebleInteres::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'nivel_interes' => 'alto',
            'estado' => 'activo',
        ]);
        $agentUser = $this->user('agent-'.$suffix.'@example.test', 'Agente Inmobiliario');
        $agent = Agente::create(['user_id' => $agentUser->id, 'numero_empleado' => 'PORTAL-'.$suffix]);
        $upcomingStart = CarbonImmutable::now(config('app.timezone'))->addDay()->setTime(10, 0);
        $appointment = Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $agentUser->id,
            'fecha_inicio' => $upcomingStart,
            'fecha_fin' => $upcomingStart->addHour(),
            'estado' => 'confirmada',
        ]);

        return [$user, $client, $property, $appointment];
    }

    private function owner(): Propietario
    {
        return Propietario::create([
            'nombre_razon_social' => 'Propietario Portal '.Str::random(5),
            'rfc' => 'POR'.Str::upper(Str::random(10)),
            'telefono' => '5555555555',
            'direccion' => 'Dirección Portal',
        ]);
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Portal',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function client(string $name, string $email, ?User $user = null): Cliente
    {
        return Cliente::create([
            'user_id' => $user?->id,
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => $email,
            'estado_cliente' => 'cliente',
        ]);
    }

    private function notification(User $user, string $type): void
    {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'tipo' => $type,
                'titulo' => 'Aviso',
                'mensaje' => 'Mensaje',
                'url' => null,
                'entidad' => ['tipo' => 'test', 'id' => 1],
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    private function apiGet(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }
}
