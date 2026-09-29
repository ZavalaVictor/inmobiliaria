<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cita;
use App\Models\CitaHistorial;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\User;
use App\Models\VisualizacionInmueble;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportesTest extends TestCase
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

    public function test_reportes_are_restricted_to_administrator_and_director(): void
    {
        $this->getJson('/api/v1/reportes/inmuebles?desde=2026-09-01&hasta=2026-09-30')->assertUnauthorized();

        foreach (['Administrador', 'Director General'] as $role) {
            $user = $this->user("report-$role@example.test", $role);
            $this->apiAs($user)->getJson('/api/v1/reportes/inmuebles?desde=2026-09-01&hasta=2026-09-30')->assertOk();
        }

        foreach (['Agente Inmobiliario', 'Asistente', 'Cliente'] as $role) {
            $user = $this->user("report-denied-$role@example.test", $role);
            $this->apiAs($user);
            self::assertSame(403, $this->getJson('/api/v1/reportes/inmuebles?desde=2026-09-01&hasta=2026-09-30')->status(), $role);
        }
    }

    public function test_date_ranges_are_required_inclusive_and_limited_to_one_year(): void
    {
        $admin = $this->user('report-ranges@example.test', 'Administrador');

        $this->apiAs($admin)->getJson('/api/v1/reportes/inmuebles')->assertUnprocessable();
        $this->apiAs($admin)->getJson('/api/v1/reportes/inmuebles?desde=2026-10-01&hasta=2026-09-01')->assertUnprocessable();
        $this->apiAs($admin)->getJson('/api/v1/reportes/inmuebles?desde=2025-01-01&hasta=2026-01-02')->assertUnprocessable();
        $this->apiAs($admin)->getJson('/api/v1/reportes/inmuebles?desde=2025-01-01&hasta=2026-01-01')->assertOk();
        $this->apiAs($admin)->getJson('/api/v1/reportes/comparativo-periodos?periodo_a_desde=2025-01-01&periodo_a_hasta=2026-01-03&periodo_b_desde=2026-01-01&periodo_b_hasta=2026-01-02')->assertUnprocessable();
    }

    public function test_inmuebles_report_derives_states_and_distributions_from_real_data(): void
    {
        $admin = $this->user('report-inmuebles@example.test', 'Administrador');
        $category = $this->category('Casas');
        $sold = $this->property('REP-SOLD', $category, 'venta', 'vendido', 'Zona Norte');
        $rented = $this->property('REP-RENT', $category, 'renta', 'disponible', 'Zona Sur');
        $available = $this->property('REP-AVAILABLE', $category, 'venta', 'disponible', 'Zona Norte');
        $inactive = $this->property('REP-INACTIVE', $category, 'venta', 'inactivo', 'Zona Sur');
        $this->operation($sold, 'venta', '2026-09-05 10:00:00');
        $this->operation($rented, 'renta', '2026-09-06 10:00:00');
        $agentUser = $this->user('report-agent@example.test', 'Agente Inmobiliario');
        $agent = Agente::create(['user_id' => $agentUser->id, 'numero_empleado' => 'REP-01']);
        AgenteInmueble::create(['agente_id' => $agent->id, 'inmueble_id' => $available->id, 'es_principal' => true]);

        $response = $this->apiAs($admin)->getJson('/api/v1/reportes/inmuebles?desde=2026-09-01&hasta=2026-09-30&zona=Zona%20Norte');

        $response->assertOk()
            ->assertJsonPath('data.resumen.total_inmuebles', 2)
            ->assertJsonPath('data.resumen.estados.disponibles.total', 1)
            ->assertJsonPath('data.resumen.estados.vendidos.total', 1)
            ->assertJsonPath('data.resumen.estados.rentados.total', 0)
            ->assertJsonPath('data.resumen.estados.disponibles.porcentaje', 50)
            ->assertJsonPath('data.graficas.distribucion_por_categoria.0.total', 2)
            ->assertJsonPath('data.graficas.carga_por_agente.0.total_inmuebles', 1)
            ->assertJsonCount(4, 'data.tablas.antiguedad_disponibles');

        self::assertSame(1, Inmueble::query()->whereKey($inactive->id)->count());
    }

    public function test_citas_report_counts_reprogramming_without_duplicate_visits_and_calculates_compliance(): void
    {
        $admin = $this->user('report-citas@example.test', 'Administrador');
        $agentUser = $this->user('report-citas-agent@example.test', 'Agente Inmobiliario');
        $agent = Agente::create(['user_id' => $agentUser->id, 'numero_empleado' => 'REP-CITA']);
        $client = $this->client('report-citas-client@example.test');
        $property = $this->property('REP-CITA', $this->category('Citas'), 'venta', 'disponible', 'Centro');
        $completed = $this->appointment($client, $agent, $property, '2026-09-05 09:00:00', 'completada');
        $cancelled = $this->appointment($client, $agent, $property, '2026-09-06 10:00:00', 'cancelada');
        $this->appointment($client, $agent, $property, '2026-09-07 11:00:00', 'programada');
        CitaHistorial::create([
            'cita_id' => $completed->id,
            'modificado_por_user_id' => $admin->id,
            'agente_anterior_id' => $agent->id,
            'agente_nuevo_id' => $agent->id,
            'fecha_inicio_anterior' => '2026-09-04 09:00:00',
            'fecha_fin_anterior' => '2026-09-04 10:00:00',
            'fecha_inicio_nueva' => '2026-09-05 09:00:00',
            'fecha_fin_nueva' => '2026-09-05 10:00:00',
            'motivo' => 'Cambio de agenda',
            'tipo_cambio' => 'reprogramacion',
            'fecha_modificacion' => '2026-09-03 10:00:00',
        ]);

        $response = $this->apiAs($admin)->getJson('/api/v1/reportes/citas?desde=2026-09-01&hasta=2026-09-30');

        $response->assertOk()
            ->assertJsonPath('data.resumen.citas_programadas', 1)
            ->assertJsonPath('data.resumen.citas_realizadas', 1)
            ->assertJsonPath('data.resumen.citas_canceladas', 1)
            ->assertJsonPath('data.resumen.citas_reprogramadas', 1)
            ->assertJsonPath('data.resumen.porcentaje_cumplimiento', 50)
            ->assertJsonPath('data.resumen.cumplimiento_denominador', 2)
            ->assertJsonPath('data.tablas.inmuebles_con_mas_visitas.0.total_visitas', 1);

        self::assertNotSame($completed->id, $cancelled->id);
    }

    public function test_consulted_properties_report_includes_zero_view_properties_and_price_semantics(): void
    {
        $admin = $this->user('report-views@example.test', 'Administrador');
        $category = $this->category('Consultadas');
        $seen = $this->property('REP-SEEN', $category, 'venta', 'disponible', 'Centro', '100000.00');
        $zero = $this->property('REP-ZERO', $category, 'venta', 'disponible', 'Centro', '150000.00');
        $rental = $this->property('REP-RENTAL', $category, 'renta', 'disponible', 'Centro', '20000.00');
        VisualizacionInmueble::create(['inmueble_id' => $seen->id, 'origen' => 'landing_publica', 'fecha_visualizacion' => '2026-09-10 10:00:00', 'created_at' => '2026-09-10 10:00:00']);
        VisualizacionInmueble::create(['inmueble_id' => $seen->id, 'origen' => 'portal_cliente', 'fecha_visualizacion' => '2025-01-01 10:00:00', 'created_at' => '2025-01-01 10:00:00']);

        $response = $this->apiAs($admin)->getJson('/api/v1/reportes/propiedades-consultadas?desde=2026-09-01&hasta=2026-09-30&precio_min=90000&precio_max=160000');

        $response->assertOk()
            ->assertJsonPath('data.resumen.total_visualizaciones', 1)
            ->assertJsonPath('data.tablas.top_consultadas.0.inmueble_id', $seen->id)
            ->assertJsonPath('data.tablas.top_consultadas.0.visualizaciones', 1)
            ->assertJsonPath('data.tablas.bottom_consultadas.0.visualizaciones', 0)
            ->assertJsonCount(4, 'data.tablas.antiguedad_vs_consultas.por_banda');

        self::assertNotContains($rental->id, array_column($response->json('data.tablas.top_consultadas'), 'inmueble_id'));
        self::assertSame(1, Inmueble::query()->whereKey($zero->id)->count());
    }

    public function test_comparative_report_calculates_trends_and_zero_base_cases(): void
    {
        $admin = $this->user('report-comparative@example.test', 'Administrador');
        $client = $this->client('report-comparative-client@example.test', '2026-08-05 10:00:00');
        $property = $this->property('REP-COMP', $this->category('Comparativo'), 'venta', 'disponible', 'Centro', '100000.00', '2026-08-06 10:00:00');
        $this->operation($property, 'venta', '2026-08-10 10:00:00', $client);
        VisualizacionInmueble::create(['inmueble_id' => $property->id, 'origen' => 'landing_publica', 'fecha_visualizacion' => '2026-09-10 10:00:00', 'created_at' => '2026-09-10 10:00:00']);

        $response = $this->apiAs($admin)->getJson('/api/v1/reportes/comparativo-periodos?periodo_a_desde=2026-08-01&periodo_a_hasta=2026-08-31&periodo_b_desde=2026-09-01&periodo_b_hasta=2026-09-30');
        $indicators = collect($response->assertOk()->json('data.indicadores'))->keyBy('indicador');

        self::assertSame(1, $indicators['clientes_nuevos']['periodo_a']);
        self::assertSame(0, $indicators['clientes_nuevos']['periodo_b']);
        self::assertSame('disminucion', $indicators['clientes_nuevos']['tendencia']);
        self::assertSame(true, $indicators['consultas_propiedades']['no_calculable']);
        self::assertNull($indicators['consultas_propiedades']['diferencia_porcentual']);
        self::assertSame('aumento', $indicators['consultas_propiedades']['tendencia']);
    }

    public function test_all_four_pdf_endpoints_return_pdf_without_data(): void
    {
        $admin = $this->user('report-pdf@example.test', 'Administrador');
        $urls = [
            '/api/v1/reportes/inmuebles/pdf?desde=2026-09-01&hasta=2026-09-30',
            '/api/v1/reportes/citas/pdf?desde=2026-09-01&hasta=2026-09-30',
            '/api/v1/reportes/propiedades-consultadas/pdf?desde=2026-09-01&hasta=2026-09-30',
            '/api/v1/reportes/comparativo-periodos/pdf?periodo_a_desde=2026-08-01&periodo_a_hasta=2026-08-31&periodo_b_desde=2026-09-01&periodo_b_hasta=2026-09-30',
        ];

        foreach ($urls as $url) {
            $response = $this->apiAs($admin)->get($url);
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            self::assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    private function user(string $email, string $role): User
    {
        $user = User::create(['nombres' => 'Reporte', 'apellido_paterno' => 'User', 'email' => $email, 'password' => 'Password123', 'estado' => 'activo']);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function apiAs(User $user): self
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web');
    }

    private function category(string $name): Categoria
    {
        return Categoria::create(['nombre' => $name, 'activo' => true]);
    }

    private function client(string $email, ?string $createdAt = null): Cliente
    {
        $client = Cliente::create(['nombres' => 'Cliente', 'apellido_paterno' => 'Reporte', 'email' => $email]);
        if ($createdAt !== null) {
            $client->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        }

        return $client->fresh();
    }

    private function property(string $code, Categoria $category, string $type, string $state, string $zone, string $price = '100000.00', ?string $createdAt = null): Inmueble
    {
        $suffix = Str::upper(Str::random(5));
        $owner = Propietario::create(['nombre_razon_social' => 'Propietario '.$suffix, 'rfc' => 'REP'.$suffix.'01', 'telefono' => '5555555555', 'direccion' => 'Dirección']);
        $data = ['propietario_id' => $owner->id, 'categoria_id' => $category->id, 'codigo' => $code, 'titulo' => 'Inmueble '.$code, 'slug' => Str::lower($code).'-'.$suffix, 'tipo_operacion' => $type, 'precio_venta' => $type === 'venta' ? $price : null, 'renta_mensual' => $type === 'renta' ? $price : null, 'calle' => 'Calle', 'colonia' => 'Centro', 'municipio' => $zone, 'estado_ubicacion' => 'Estado', 'codigo_postal' => '01000', 'estado_disponibilidad' => $state, 'publicado' => true, 'fecha_publicacion' => '2026-09-01 10:00:00'];
        $property = Inmueble::create($data);
        if ($createdAt !== null) {
            $property->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        }

        return $property->fresh();
    }

    private function operation(Inmueble $property, string $type, string $date, ?Cliente $client = null): Operacion
    {
        $client ??= $this->client('operation-'.Str::random(6).'@example.test');
        $opportunity = Oportunidad::create(['cliente_id' => $client->id, 'inmueble_id' => $property->id, 'titulo' => 'Operación reporte', 'etapa' => 'cierre', 'estado' => 'ganada']);

        return Operacion::create(['oportunidad_id' => $opportunity->id, 'cliente_id' => $client->id, 'inmueble_id' => $property->id, 'registrado_por_user_id' => User::query()->firstOrFail()->id, 'tipo_operacion' => $type, 'monto' => '100000.00', 'fecha_operacion' => $date, 'estado' => 'registrada']);
    }

    private function appointment(Cliente $client, Agente $agent, Inmueble $property, string $start, string $state): Cita
    {
        return Cita::create(['cliente_id' => $client->id, 'agente_id' => $agent->id, 'inmueble_id' => $property->id, 'creado_por_user_id' => User::query()->firstOrFail()->id, 'fecha_inicio' => $start, 'fecha_fin' => Carbon::parse($start)->addHour(), 'estado' => $state]);
    }
}
