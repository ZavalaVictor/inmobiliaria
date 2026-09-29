<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\OperacionAgente;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\SolicitudInformacion;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Carbon::setTestNow('2026-09-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_requires_authentication_and_rejects_clients(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();

        $client = $this->user('dashboard-client@example.test', 'Cliente');
        $this->actingAs($client, 'web')->getJson('/api/v1/dashboard')->assertForbidden();
    }

    public function test_inactive_account_is_rejected_by_account_active_middleware(): void
    {
        $admin = $this->user('dashboard-inactive@example.test', 'Administrador');
        $admin->update(['estado' => 'bloqueado']);

        $this->actingAs($admin, 'web')->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_period_is_validated_and_defaults_to_current_month(): void
    {
        $admin = $this->user('dashboard-period@example.test', 'Administrador');

        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.periodo.clave', 'mes')
            ->assertJsonPath('data.periodo.inicio', '2026-09-01T00:00:00.000000Z')
            ->assertJsonPath('data.periodo.fin', '2026-09-30T23:59:59.999999Z');

        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/dashboard?periodo=trimestre')
            ->assertOk()
            ->assertJsonPath('data.periodo.clave', 'trimestre')
            ->assertJsonPath('data.periodo.inicio', '2026-07-01T00:00:00.000000Z');

        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/dashboard?periodo=anio')
            ->assertOk()
            ->assertJsonPath('data.periodo.clave', 'anio')
            ->assertJsonPath('data.periodo.inicio', '2026-01-01T00:00:00.000000Z');

        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/dashboard?periodo=semana')
            ->assertUnprocessable();
    }

    public function test_administrator_receives_global_metrics_charts_lists_and_unread_count(): void
    {
        $admin = $this->user('dashboard-admin@example.test', 'Administrador');
        $agentUser = $this->user('dashboard-admin-agent@example.test', 'Agente Inmobiliario');
        $agent = Agente::create(['user_id' => $agentUser->id, 'numero_empleado' => 'DASH-ADM-01']);
        $client = $this->client('dashboard-admin-client@example.test');
        $property = $this->property('DASH-ADM-1', 'disponible');
        $sold = $this->property('DASH-ADM-2', 'vendido');
        AgenteInmueble::create(['agente_id' => $agent->id, 'inmueble_id' => $property->id]);
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->id]);

        $opportunity = $this->opportunity($client, $property, 'activa', 'negociacion');
        $this->operation($opportunity, $client, $property, '100000.50', '2026-09-10 09:00:00');
        $anulledOpportunity = $this->opportunity($client, $property, 'perdida', 'cita');
        $anulled = $this->operation($anulledOpportunity, $client, $property, '999999.99', '2026-09-11 09:00:00');
        $anulled->update(['estado' => 'anulada']);
        $this->request($property, $client, '2026-09-05 09:00:00');
        Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $admin->id,
            'fecha_inicio' => '2026-09-20 09:00:00',
            'fecha_fin' => '2026-09-20 10:00:00',
            'estado' => 'programada',
        ]);
        $this->notification($admin, false);
        $this->notification($admin, true);

        $response = $this->actingAs($admin, 'web')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.rol', 'Administrador')
            ->assertJsonPath('data.moneda', 'MXN')
            ->assertJsonPath('data.resumen.inmuebles_totales', 2)
            ->assertJsonPath('data.resumen.inmuebles_disponibles', 1)
            ->assertJsonPath('data.resumen.clientes_totales', 1)
            ->assertJsonPath('data.resumen.oportunidades_abiertas', 1)
            ->assertJsonPath('data.resumen.solicitudes_nuevas', 1)
            ->assertJsonPath('data.resumen.citas_proximas', 1)
            ->assertJsonPath('data.resumen.operaciones_registradas', 1)
            ->assertJsonPath('data.resumen.monto_operaciones_registradas', 100000.5)
            ->assertJsonPath('data.notificaciones.no_leidas', 1)
            ->assertJsonCount(12, 'data.graficas.operaciones_por_mes')
            ->assertJsonCount(5, 'data.graficas.oportunidades_por_etapa')
            ->assertJsonCount(4, 'data.graficas.inmuebles_por_estado')
            ->assertJsonCount(1, 'data.listas.solicitudes_recientes')
            ->assertJsonCount(1, 'data.listas.citas_proximas');

        self::assertTrue(is_float($response->json('data.resumen.monto_operaciones_registradas')) || is_int($response->json('data.resumen.monto_operaciones_registradas')));
        self::assertArrayNotHasKey('operaciones_cerradas', $response->json('data.resumen'));
        self::assertArrayNotHasKey('monto_operaciones', $response->json('data.resumen'));
    }

    public function test_operations_chart_has_exactly_twelve_chronological_buckets_and_zeroes(): void
    {
        $admin = $this->user('dashboard-months@example.test', 'Administrador');
        $client = $this->client('dashboard-months-client@example.test');
        $property = $this->property('DASH-MONTHS');

        foreach ([
            ['2025-10-15 10:00:00', '1000.00'],
            ['2026-03-15 10:00:00', '2000.00'],
            ['2026-09-10 10:00:00', '3000.00'],
            ['2024-01-15 10:00:00', '9000.00'],
        ] as [$date, $amount]) {
            $opportunity = $this->opportunity($client, $property);
            $this->operation($opportunity, $client, $property, $amount, $date);
        }

        $months = $this->actingAs($admin, 'web')
            ->getJson('/api/v1/dashboard?periodo=anio')
            ->assertOk()
            ->json('data.graficas.operaciones_por_mes');

        self::assertCount(12, $months);
        self::assertSame('2025-10', $months[0]['mes']);
        self::assertSame('2026-09', $months[11]['mes']);
        self::assertSame(0, $months[1]['total']);
        self::assertSame(1, $months[5]['total']);
        self::assertSame(0, $months[10]['total']);
    }

    public function test_agent_dashboard_uses_only_existing_agent_visibility_scopes(): void
    {
        $agentAUser = $this->user('dashboard-agent-a@example.test', 'Agente Inmobiliario');
        $agentBUser = $this->user('dashboard-agent-b@example.test', 'Agente Inmobiliario');
        $agentA = Agente::create(['user_id' => $agentAUser->id, 'numero_empleado' => 'DASH-A']);
        $agentB = Agente::create(['user_id' => $agentBUser->id, 'numero_empleado' => 'DASH-B']);
        $clientA = $this->client('dashboard-agent-client-a@example.test');
        $clientB = $this->client('dashboard-agent-client-b@example.test');
        $propertyA = $this->property('DASH-A-P');
        $propertyB = $this->property('DASH-B-P');
        AgenteInmueble::create(['agente_id' => $agentA->id, 'inmueble_id' => $propertyA->id]);
        AgenteInmueble::create(['agente_id' => $agentB->id, 'inmueble_id' => $propertyB->id]);
        ClienteAgente::create(['cliente_id' => $clientA->id, 'agente_id' => $agentA->id]);
        ClienteAgente::create(['cliente_id' => $clientB->id, 'agente_id' => $agentB->id]);
        $opportunityA = $this->opportunity($clientA, $propertyA, 'activa', 'negociacion', $agentA);
        $opportunityB = $this->opportunity($clientB, $propertyB, 'activa', 'cita', $agentB);
        $operationA = $this->operation($opportunityA, $clientA, $propertyA, '1000.00', '2026-09-10 09:00:00');
        $operationB = $this->operation($opportunityB, $clientB, $propertyB, '2000.00', '2026-09-10 09:00:00');
        OperacionAgente::create(['operacion_id' => $operationA->id, 'agente_id' => $agentA->id, 'es_principal' => true]);
        OperacionAgente::create(['operacion_id' => $operationB->id, 'agente_id' => $agentB->id, 'es_principal' => true]);
        $this->request($propertyA, $clientA);
        $this->request($propertyB, $clientB);
        Cita::create([
            'cliente_id' => $clientA->id,
            'agente_id' => $agentA->id,
            'inmueble_id' => $propertyA->id,
            'creado_por_user_id' => $agentAUser->id,
            'fecha_inicio' => '2026-09-20 09:00:00',
            'fecha_fin' => '2026-09-20 10:00:00',
            'estado' => 'programada',
        ]);
        Cita::create([
            'cliente_id' => $clientB->id,
            'agente_id' => $agentB->id,
            'inmueble_id' => $propertyB->id,
            'creado_por_user_id' => $agentBUser->id,
            'fecha_inicio' => '2026-09-20 11:00:00',
            'fecha_fin' => '2026-09-20 12:00:00',
            'estado' => 'programada',
        ]);
        $this->notification($agentAUser, false);
        $this->notification($agentBUser, false);

        $response = $this->actingAs($agentAUser, 'web')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.rol', 'Agente Inmobiliario')
            ->assertJsonPath('data.resumen.inmuebles_asignados', 1)
            ->assertJsonPath('data.resumen.clientes_asignados', 1)
            ->assertJsonPath('data.resumen.solicitudes_en_atencion', 0)
            ->assertJsonPath('data.resumen.citas_proximas', 1)
            ->assertJsonPath('data.resumen.oportunidades_activas', 1)
            ->assertJsonPath('data.resumen.operaciones_propias', 1)
            ->assertJsonPath('data.notificaciones.no_leidas', 1)
            ->assertJsonCount(1, 'data.listas.proximas_citas')
            ->assertJsonCount(1, 'data.listas.solicitudes_pendientes')
            ->assertJsonCount(1, 'data.listas.operaciones_recientes')
            ->assertJsonMissingPath('data.resumen.comisiones')
            ->assertJsonMissingPath('data.resumen.monto_comisiones')
            ->assertJsonPath('data.listas.proximas_citas.0.cliente.id', $clientA->id)
            ->assertJsonPath('data.listas.proximas_citas.0.inmueble.id', $propertyA->id)
            ->assertJsonPath('data.listas.operaciones_recientes.0.cliente.id', $clientA->id)
            ->assertJsonPath('data.listas.operaciones_recientes.0.inmueble.id', $propertyA->id)
            ->assertJsonPath('data.listas.solicitudes_pendientes.0.inmueble.id', $propertyA->id);
    }

    public function test_agent_without_profile_and_unknown_users_fail_closed(): void
    {
        $agent = $this->user('dashboard-agent-no-profile@example.test', 'Agente Inmobiliario');
        $unknown = $this->user('dashboard-unknown@example.test');

        $this->actingAs($agent, 'web')->getJson('/api/v1/dashboard')->assertForbidden();
        $this->actingAs($unknown, 'web')->getJson('/api/v1/dashboard')->assertForbidden();
    }

    public function test_assistant_gets_operational_snapshot_without_financial_data(): void
    {
        $assistant = $this->user('dashboard-assistant@example.test', 'Asistente');
        $client = $this->client('dashboard-assistant-client@example.test');
        $property = $this->property('DASH-ASST');
        SolicitudInformacion::create(['nombre' => 'Nueva', 'email' => 'new@example.test', 'estado' => 'nueva']);
        SolicitudInformacion::create(['nombre' => 'Atención', 'email' => 'attention@example.test', 'estado' => 'en_atencion']);
        Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => Agente::create(['user_id' => $this->user('dashboard-assistant-agent@example.test', 'Agente Inmobiliario')->id, 'numero_empleado' => 'DASH-ASST-A'])->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $assistant->id,
            'fecha_inicio' => '2026-09-15 14:00:00',
            'fecha_fin' => '2026-09-15 15:00:00',
            'estado' => 'programada',
        ]);
        $this->notification($assistant, false);

        $response = $this->actingAs($assistant, 'web')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.rol', 'Asistente')
            ->assertJsonPath('data.resumen.solicitudes_nuevas', 1)
            ->assertJsonPath('data.resumen.solicitudes_en_atencion', 1)
            ->assertJsonPath('data.resumen.citas_hoy', 1)
            ->assertJsonPath('data.resumen.citas_proximas', 1)
            ->assertJsonPath('data.resumen.inmuebles_disponibles', 1)
            ->assertJsonPath('data.resumen.clientes_totales', 1)
            ->assertJsonPath('data.notificaciones.no_leidas', 1)
            ->assertJsonPath('data.graficas', [])
            ->assertJsonMissingPath('data.resumen.monto_operaciones_registradas')
            ->assertJsonMissingPath('data.resumen.comisiones');
    }

    public function test_director_gets_global_executive_data_without_operational_lists_or_commissions(): void
    {
        $director = $this->user('dashboard-director@example.test', 'Director General');
        $this->property('DASH-DIRECTOR');
        $this->notification($director, false);

        $response = $this->actingAs($director, 'web')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.rol', 'Director General')
            ->assertJsonPath('data.resumen.operaciones_registradas', 0)
            ->assertJsonPath('data.resumen.monto_operaciones_registradas', 0)
            ->assertJsonPath('data.listas', [])
            ->assertJsonPath('data.notificaciones.no_leidas', 1)
            ->assertJsonCount(12, 'data.graficas.operaciones_por_mes')
            ->assertJsonMissingPath('data.resumen.comisiones')
            ->assertJsonMissingPath('data.resumen.monto_comisiones')
            ->assertJsonMissingPath('data.resumen.respaldos');

        self::assertArrayNotHasKey('operaciones_cerradas', $response->json('data.resumen'));
        self::assertArrayNotHasKey('monto_operaciones', $response->json('data.resumen'));
    }

    public function test_multi_role_uses_the_existing_explicit_dashboard_precedence(): void
    {
        $user = $this->user('dashboard-multi-role@example.test', 'Agente Inmobiliario');
        $user->assignRole('Administrador');

        $this->actingAs($user->fresh(), 'web')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.rol', 'Administrador');
    }

    private function user(string $email, ?string $role = null): User
    {
        $user = User::create([
            'nombres' => 'Dashboard',
            'apellido_paterno' => 'User',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    private function client(string $email): Cliente
    {
        return Cliente::create([
            'nombres' => 'Cliente',
            'apellido_paterno' => 'Dashboard',
            'email' => $email,
        ]);
    }

    private function property(string $code, string $availability = 'disponible'): Inmueble
    {
        $suffix = Str::upper(Str::random(6));
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario '.$suffix,
            'rfc' => 'DASH'.$suffix.'01',
            'telefono' => '5555555555',
            'direccion' => 'Dirección Dashboard',
        ]);
        $category = Categoria::create(['nombre' => 'Categoría '.$suffix]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => $code,
            'titulo' => 'Inmueble '.$code,
            'slug' => Str::lower($code).'-'.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle Dashboard',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '01000',
            'estado_disponibilidad' => $availability,
            'publicado' => true,
        ]);
    }

    private function opportunity(Cliente $client, Inmueble $property, string $state = 'activa', string $stage = 'contacto_inicial', ?Agente $agent = null): Oportunidad
    {
        return Oportunidad::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'agente_principal_id' => $agent?->id,
            'titulo' => 'Oportunidad Dashboard',
            'estado' => $state,
            'etapa' => $stage,
        ]);
    }

    private function operation(Oportunidad $opportunity, Cliente $client, Inmueble $property, string $amount, string $date): Operacion
    {
        return Operacion::create([
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'registrado_por_user_id' => User::query()->firstOrFail()->id,
            'tipo_operacion' => 'venta',
            'monto' => $amount,
            'fecha_operacion' => $date,
            'estado' => 'registrada',
        ]);
    }

    private function request(?Inmueble $property = null, ?Cliente $client = null, ?string $date = null): SolicitudInformacion
    {
        $request = SolicitudInformacion::create([
            'inmueble_id' => $property?->id,
            'cliente_id' => $client?->id,
            'nombre' => 'Solicitud Dashboard',
            'email' => 'request-'.Str::random(8).'@example.test',
            'estado' => 'nueva',
        ]);

        if ($date !== null) {
            $request->forceFill(['fecha_solicitud' => $date, 'created_at' => $date, 'updated_at' => $date])->saveQuietly();
        }

        return $request->fresh();
    }

    private function notification(User $user, bool $read): void
    {
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'dashboard.test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['tipo' => 'dashboard_test', 'titulo' => 'Dashboard', 'mensaje' => 'Test'],
            'read_at' => $read ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
