<?php

namespace Tests\Feature\Api;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClienteInmuebleInteres;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use App\Models\VisualizacionInmueble;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VisualizacionesInmueblesTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_endpoint_records_one_server_controlled_event(): void
    {
        $inmueble = $this->property('VIS-001', true);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders([
                'User-Agent' => 'Visualizador/1.0',
                'Referer' => 'https://example.test/inmuebles/vis-001',
            ])
            ->postJson($this->publicUrl($inmueble), [])
            ->assertNoContent();

        $visualizacion = VisualizacionInmueble::query()->firstOrFail();
        self::assertSame($inmueble->id, $visualizacion->inmueble_id);
        self::assertSame('landing_publica', $visualizacion->origen->value);
        self::assertNotNull($visualizacion->fecha_visualizacion);
        self::assertNotNull($visualizacion->created_at);
        self::assertSame(64, strlen((string) $visualizacion->ip_hash));
        self::assertSame(
            hash_hmac('sha256', '203.0.113.10', (string) config('app.key')),
            $visualizacion->ip_hash,
        );
        self::assertNotSame('203.0.113.10', $visualizacion->ip_hash);
        self::assertSame('Visualizador/1.0', $visualizacion->user_agent);
        self::assertSame('https://example.test/inmuebles/vis-001', $visualizacion->referer);
        self::assertNull($visualizacion->session_id);
        self::assertCount(0, ClienteInmuebleInteres::query()->get());
        self::assertSame(1, Inmueble::query()->whereKey($inmueble->id)->count());
    }

    public function test_public_endpoint_only_accepts_published_non_deleted_properties(): void
    {
        $unpublished = $this->property('VIS-002', false);
        $deleted = $this->property('VIS-003', true);
        $deleted->delete();

        $this->postJson($this->publicUrl($unpublished), [])->assertNotFound();
        $this->postJson($this->publicUrl($deleted), [])->assertNotFound();
        $this->postJson('/api/v1/public/inmuebles/999999/visualizaciones', [])->assertNotFound();
        self::assertDatabaseCount('visualizaciones_inmuebles', 0);
    }

    public function test_registration_payload_cannot_control_internal_metadata(): void
    {
        $inmueble = $this->property('VIS-004', true);
        $fields = [
            'inmueble_id' => $inmueble->id,
            'session_id' => 'session-spoofed',
            'ip_hash' => str_repeat('a', 64),
            'ip' => '203.0.113.1',
            'user_agent' => 'spoofed',
            'referer' => 'spoofed',
            'origen' => 'interno',
            'fecha_visualizacion' => '2026-10-01 10:00:00',
            'created_at' => '2026-10-01 10:00:00',
            'updated_at' => '2026-10-01 10:00:00',
            'cliente_id' => 1,
            'user_id' => 1,
            'agente_id' => 1,
            'roles' => ['Administrador'],
            'permissions' => ['visualizaciones.ver'],
            'permisos' => ['visualizaciones.ver'],
        ];

        foreach ($fields as $field => $value) {
            $this->postJson($this->publicUrl($inmueble), [$field => $value])->assertUnprocessable();
        }

        self::assertDatabaseCount('visualizaciones_inmuebles', 0);
    }

    public function test_same_session_and_ip_are_not_deduplicated(): void
    {
        $inmueble = $this->property('VIS-005', true);
        $headers = [
            'User-Agent' => 'Repeated/1.0',
            'Referer' => 'https://example.test/repeated',
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->withHeaders($headers)
            ->postJson($this->publicUrl($inmueble), [])
            ->assertNoContent();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->withHeaders($headers)
            ->postJson($this->publicUrl($inmueble), [])
            ->assertNoContent();

        self::assertSame(2, VisualizacionInmueble::query()->count());
        self::assertSame(2, VisualizacionInmueble::query()->where('inmueble_id', $inmueble->id)->count());
    }

    public function test_public_endpoint_is_rate_limited(): void
    {
        $inmueble = $this->property('VIS-006', true);
        $serverVariables = ['REMOTE_ADDR' => '198.51.100.60'];

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->withServerVariables($serverVariables)
                ->postJson($this->publicUrl($inmueble), [])
                ->assertNoContent();
        }

        $this->withServerVariables($serverVariables)
            ->postJson($this->publicUrl($inmueble), [])
            ->assertTooManyRequests();
    }

    public function test_client_can_record_only_a_view_of_an_inmueble_visible_to_that_client(): void
    {
        $clientUser = $this->user('portal-visualizations@example.test', 'Cliente');
        $client = $this->client('Cliente portal', $clientUser);
        $visible = $this->property('VIS-007', false);
        $hidden = $this->property('VIS-008', false);
        ClienteInmuebleInteres::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $visible->id,
            'estado' => 'activo',
        ]);

        $this->apiPost($this->portalUrl($visible), $clientUser, [])->assertNoContent();
        $this->apiPost($this->portalUrl($hidden), $clientUser, [])->assertForbidden();

        $visualizacion = VisualizacionInmueble::query()->firstOrFail();
        self::assertSame('portal_cliente', $visualizacion->origen->value);
        self::assertSame($visible->id, $visualizacion->inmueble_id);
    }

    public function test_portal_endpoint_rejects_non_clients_and_clients_without_profile(): void
    {
        $inmueble = $this->property('VIS-009', true);
        $admin = $this->user('admin-portal-visualizations@example.test', 'Administrador');
        $agent = $this->user('agent-portal-visualizations@example.test', 'Agente Inmobiliario');
        $assistant = $this->user('assistant-portal-visualizations@example.test', 'Asistente');
        $director = $this->user('director-portal-visualizations@example.test', 'Director General');
        $orphanClient = $this->user('orphan-client-visualizations@example.test', 'Cliente');

        foreach ([$admin, $agent, $assistant, $director, $orphanClient] as $user) {
            $this->apiPost($this->portalUrl($inmueble), $user, [])->assertForbidden();
        }

        self::assertDatabaseCount('visualizaciones_inmuebles', 0);
    }

    public function test_portal_endpoint_does_not_accept_origin_or_metadata_from_payload(): void
    {
        $clientUser = $this->user('portal-validation@example.test', 'Cliente');
        $client = $this->client('Cliente validación', $clientUser);
        $inmueble = $this->property('VIS-010', true);
        ClienteInmuebleInteres::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $inmueble->id,
            'estado' => 'activo',
        ]);

        $this->apiPost($this->portalUrl($inmueble), $clientUser, [
            'origen' => 'interno',
        ])->assertUnprocessable();
        self::assertDatabaseCount('visualizaciones_inmuebles', 0);
    }

    public function test_only_administrator_can_query_and_resource_is_private(): void
    {
        $admin = $this->user('admin-index-visualizations@example.test', 'Administrador');
        $agent = $this->user('agent-index-visualizations@example.test', 'Agente Inmobiliario');
        $assistant = $this->user('assistant-index-visualizations@example.test', 'Asistente');
        $director = $this->user('director-index-visualizations@example.test', 'Director General');
        $clientUser = $this->user('client-index-visualizations@example.test', 'Cliente');
        $inmueble = $this->property('VIS-011', true);
        $visualizacion = VisualizacionInmueble::create([
            'inmueble_id' => $inmueble->id,
            'session_id' => 'internal-session',
            'ip_hash' => str_repeat('b', 64),
            'user_agent' => 'Internal agent',
            'referer' => 'https://example.test',
            'origen' => 'interno',
            'fecha_visualizacion' => '2026-09-01 10:00:00',
        ]);

        $this->getJson('/api/v1/visualizaciones-inmuebles')->assertUnauthorized();
        $this->apiGet('/api/v1/visualizaciones-inmuebles', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.id', $visualizacion->id)
            ->assertJsonMissingPath('data.0.session_id')
            ->assertJsonMissingPath('data.0.ip_hash')
            ->assertJsonMissingPath('data.0.user_agent')
            ->assertJsonMissingPath('data.0.referer')
            ->assertJsonPath('data.0.inmueble.id', $inmueble->id);

        foreach ([$agent, $assistant, $director, $clientUser] as $user) {
            $this->apiGet('/api/v1/visualizaciones-inmuebles', $user)->assertForbidden();
        }
    }

    public function test_admin_index_filters_orders_and_paginates_without_requiring_related_property(): void
    {
        $admin = $this->user('admin-filters-visualizations@example.test', 'Administrador');
        $first = $this->property('VIS-012', true);
        $second = $this->property('VIS-013', true);
        $softDeleted = $this->property('VIS-014', true);
        $firstView = VisualizacionInmueble::create([
            'inmueble_id' => $first->id,
            'origen' => 'landing_publica',
            'fecha_visualizacion' => '2026-09-01 10:00:00',
        ]);
        VisualizacionInmueble::create([
            'inmueble_id' => $second->id,
            'origen' => 'portal_cliente',
            'fecha_visualizacion' => '2026-09-03 10:00:00',
        ]);
        $historicalView = VisualizacionInmueble::create([
            'inmueble_id' => $softDeleted->id,
            'origen' => 'interno',
            'fecha_visualizacion' => '2026-09-02 10:00:00',
        ]);
        $softDeleted->delete();

        $base = '/api/v1/visualizaciones-inmuebles';
        $this->apiGet($base.'?inmueble_id='.$first->id.'&origen=landing_publica', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $firstView->id);
        $this->apiGet($base.'?fecha_desde=2026-09-01 00:00:00&fecha_hasta=2026-09-02 23:59:59', $admin)
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->apiGet($base.'?per_page=1&sort=inmueble_id&direction=asc', $admin)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);
        $this->apiGet($base.'?sort=invalid', $admin)->assertUnprocessable();
        $this->apiGet($base.'?direction=invalid', $admin)->assertUnprocessable();
        $this->apiGet($base.'?fecha_desde=2026-09-03 00:00:00&fecha_hasta=2026-09-02 00:00:00', $admin)
            ->assertUnprocessable();

        $this->apiGet($base.'?origen=interno', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.id', $historicalView->id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function apiPost(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson($uri, $payload);
    }

    private function apiGet(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }

    private function publicUrl(Inmueble $inmueble): string
    {
        return '/api/v1/public/inmuebles/'.$inmueble->id.'/visualizaciones';
    }

    private function portalUrl(Inmueble $inmueble): string
    {
        return '/api/v1/inmuebles/'.$inmueble->id.'/visualizaciones';
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Visualizaciones',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function client(string $name, User $user): Cliente
    {
        return Cliente::create([
            'user_id' => $user->id,
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => 'visual-client-'.(++self::$sequence).'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function property(string $code, bool $published): Inmueble
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
            'publicado' => $published,
        ]);
    }
}
