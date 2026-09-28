<?php

namespace Tests\Feature\Api;

use App\Models\Bitacora;
use App\Models\User;
use App\Services\BitacoraService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class BitacoraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_bitacora_requires_authentication_and_is_admin_only(): void
    {
        $registro = Bitacora::create(['accion' => 'usuario_creado', 'entidad' => 'usuario', 'descripcion' => 'Prueba']);
        $this->getJson('/api/v1/bitacora')->assertUnauthorized();
        $this->getJson('/api/v1/bitacora/exportar')->assertUnauthorized();
        $this->getJson('/api/v1/bitacora/'.$registro->id)->assertUnauthorized();

        foreach (['Agente Inmobiliario', 'Asistente', 'Director General', 'Cliente'] as $role) {
            $user = $this->user($role.'@bitacora.test', $role);
            $this->apiGet('/api/v1/bitacora', $user)->assertForbidden();
            $this->apiGet('/api/v1/bitacora/exportar', $user)->assertForbidden();
            $this->apiGet('/api/v1/bitacora/'.$registro->id, $user)->assertForbidden();
        }
    }

    public function test_administrator_can_index_show_and_export_without_sensitive_fields(): void
    {
        $admin = $this->user('admin-bitacora@example.test', 'Administrador');
        $actor = $this->user('actor-bitacora@example.test', 'Agente Inmobiliario');
        $registro = Bitacora::create([
            'user_id' => $actor->id,
            'accion' => 'usuario_actualizado',
            'entidad' => 'usuario',
            'entidad_id' => $actor->id,
            'descripcion' => 'Auditoría UTF-8 áé',
            'datos_anteriores' => ['estado' => 'pendiente'],
            'datos_nuevos' => ['estado' => 'activo'],
            'ip_hash' => str_repeat('a', 64),
            'user_agent' => 'private-agent',
        ]);

        $this->apiGet('/api/v1/bitacora', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.id', $registro->id)
            ->assertJsonMissingPath('data.0.datos_anteriores')
            ->assertJsonMissingPath('data.0.ip_hash')
            ->assertJsonMissingPath('data.0.user_agent');

        $this->apiGet('/api/v1/bitacora/'.$registro->id, $admin)
            ->assertOk()
            ->assertJsonPath('data.datos_anteriores.estado', 'pendiente')
            ->assertJsonPath('data.datos_nuevos.estado', 'activo')
            ->assertJsonMissingPath('data.ip_hash')
            ->assertJsonMissingPath('data.user_agent');

        $response = $this->actingAs($admin, 'web')->get('/api/v1/bitacora/exportar');
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        self::assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        self::assertStringContainsString('actor_nombre', $response->streamedContent());
        self::assertStringContainsString('Bitacora Usuario', $response->streamedContent());
        self::assertStringContainsString('Auditoría UTF-8 áé', $response->streamedContent());
        self::assertStringNotContainsString('datos_anteriores', $response->streamedContent());
        self::assertStringNotContainsString(str_repeat('a', 64), $response->streamedContent());
        self::assertStringNotContainsString('private-agent', $response->streamedContent());

        Bitacora::create([
            'accion' => 'agente_creado',
            'entidad' => 'agente',
            'descripcion' => 'Evento no solicitado',
        ]);
        $filteredExport = $this->actingAs($admin, 'web')->get('/api/v1/bitacora/exportar?accion=usuario_actualizado');
        $filteredExport->assertOk();
        self::assertStringContainsString('Auditoría UTF-8 áé', $filteredExport->streamedContent());
        self::assertStringNotContainsString('Evento no solicitado', $filteredExport->streamedContent());
    }

    public function test_index_filters_search_paginates_and_orders(): void
    {
        $admin = $this->user('filters-bitacora@example.test', 'Administrador');
        Bitacora::create([
            'accion' => 'agente_creado',
            'entidad' => 'agente',
            'entidad_id' => 10,
            'descripcion' => 'Evento visible',
            'fecha_evento' => '2026-09-20 10:00:00',
        ]);
        $other = Bitacora::create([
            'accion' => 'operacion_creada',
            'entidad' => 'operacion',
            'entidad_id' => 20,
            'descripcion' => 'Otro evento',
            'fecha_evento' => '2026-09-21 10:00:00',
        ]);

        $this->apiGet('/api/v1/bitacora?entidad=agente&q=visible&fecha_desde=2026-09-01 00:00:00&fecha_hasta=2026-09-30 23:59:59', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/bitacora?per_page=1&sort=fecha_evento&direction=asc', $admin)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.entidad', 'agente');
        $this->apiGet('/api/v1/bitacora?fecha_desde=2026-10-01 00:00:00&fecha_hasta=2026-09-01 00:00:00', $admin)
            ->assertUnprocessable();
        $this->apiGet('/api/v1/bitacora/'.$other->id, $admin)->assertOk();
    }

    public function test_bitacora_is_append_only_and_missing_record_is_not_found(): void
    {
        $admin = $this->user('append-bitacora@example.test', 'Administrador');
        $registro = Bitacora::create(['accion' => 'usuario_creado', 'entidad' => 'usuario']);
        $url = '/api/v1/bitacora';

        $this->actingAs($admin, 'web')->postJson($url, [])->assertMethodNotAllowed();
        $this->apiPatch($url.'/'.$registro->id, $admin, [])->assertMethodNotAllowed();
        $this->actingAs($admin, 'web')->putJson($url.'/'.$registro->id, [])->assertMethodNotAllowed();
        $this->apiDelete($url.'/'.$registro->id, $admin)->assertMethodNotAllowed();
        $this->apiGet($url.'/999999', $admin)->assertNotFound();
    }

    public function test_missing_permission_unknown_role_and_no_role_fail_closed(): void
    {
        $registro = Bitacora::create(['accion' => 'usuario_creado', 'entidad' => 'usuario']);
        $admin = $this->user('admin-without-bitacora-permission@example.test', 'Administrador');
        Role::findByName('Administrador', 'web')->revokePermissionTo(['bitacora.ver', 'bitacora.exportar']);
        $unknown = $this->user('unknown-bitacora@example.test', null);
        $unknown->assignRole(Role::create(['name' => 'Rol inesperado', 'guard_name' => 'web']));
        $withoutRole = $this->user('without-role-bitacora@example.test', null);

        foreach ([$admin, $unknown, $withoutRole] as $user) {
            $this->apiGet('/api/v1/bitacora', $user)->assertForbidden();
            $this->apiGet('/api/v1/bitacora/'.$registro->id, $user)->assertForbidden();
            $this->apiGet('/api/v1/bitacora/exportar', $user)->assertForbidden();
        }
    }

    public function test_service_sanitizes_snapshots_and_hashes_ip(): void
    {
        $admin = $this->user('service-bitacora@example.test', 'Administrador');
        $request = Request::create('/api/v1/bitacora', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.10']);
        $request->headers->set('User-Agent', str_repeat('A', 600));
        $this->app->instance('request', $request);

        $registro = app(BitacoraService::class)->record(
            $admin,
            'usuario_actualizado',
            'usuario',
            $admin->id,
            'Seguro',
            ['nested' => ['password' => 'secret', 'safe' => 'ok'], 'firebase_path' => 'private'],
            [
                'password_confirmation' => 'secret',
                'remember_token' => 'secret',
                'token' => 'secret',
                'access_token' => 'secret',
                'session_id' => 'secret',
                'secret' => 'secret',
                'credentials' => ['firebase_path' => 'secret'],
                'estado' => 'activo',
            ],
        );

        self::assertSame(64, strlen((string) $registro->ip_hash));
        self::assertSame(500, strlen((string) $registro->user_agent));
        self::assertSame(['nested' => ['safe' => 'ok']], $registro->datos_anteriores);
        self::assertSame(['estado' => 'activo'], $registro->datos_nuevos);
        self::assertStringNotContainsString('203.0.113.10', (string) DB::table('bitacora')->where('id', $registro->id)->value('ip_hash'));
    }

    private function user(string $email, ?string $role): User
    {
        $user = User::create([
            'nombres' => 'Bitacora',
            'apellido_paterno' => 'Usuario',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    private function apiGet(string $url, User $user): TestResponse
    {
        return $this->actingAs($user, 'web')->getJson($url);
    }

    private function apiPatch(string $url, User $user, array $payload): TestResponse
    {
        return $this->actingAs($user, 'web')->patchJson($url, $payload);
    }

    private function apiDelete(string $url, User $user): TestResponse
    {
        return $this->actingAs($user, 'web')->deleteJson($url);
    }
}
