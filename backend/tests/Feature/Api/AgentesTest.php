<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AgentesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_agents(): void
    {
        $this->getJson('/api/v1/agentes')->assertUnauthorized();
    }

    public function test_administrator_can_manage_agents(): void
    {
        $administrator = $this->user('admin-agentes@example.test', 'Administrador');
        $agentUser = $this->user('agent-create@example.test', 'Agente Inmobiliario');

        $response = $this->apiPost('/api/v1/agentes', $administrator, [
            'user_id' => $agentUser->id,
            'numero_empleado' => 'AG-001',
            'telefono_corporativo' => '5551112222',
            'zona_asignacion' => 'Norte',
            'horario' => 'Lunes a viernes',
            'fecha_contratacion' => '2026-09-25',
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $agentUser->id)
            ->assertJsonPath('data.numero_empleado', 'AG-001')
            ->assertJsonPath('data.estado_laboral', 'activo')
            ->assertJsonPath('data.porcentaje_comision', '0.00')
            ->assertJsonPath('data.user.email', $agentUser->email)
            ->assertJsonMissingPath('data.foto_path')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.roles');

        $agente = Agente::query()->findOrFail($response->json('data.id'));

        $this->apiGet('/api/v1/agentes/'.$agente->id, $administrator)
            ->assertOk()
            ->assertJsonPath('data.user_id', $agentUser->id);

        $this->apiPatch('/api/v1/agentes/'.$agente->id, $administrator, [
            'numero_empleado' => 'AG-002',
            'porcentaje_comision' => '100.00',
            'estado_laboral' => 'inactivo',
        ])->assertOk()
            ->assertJsonPath('data.numero_empleado', 'AG-002')
            ->assertJsonPath('data.porcentaje_comision', '100.00')
            ->assertJsonPath('data.estado_laboral', 'inactivo');

        $this->apiDelete('/api/v1/agentes/'.$agente->id, $administrator)
            ->assertNoContent();

        self::assertSoftDeleted('agentes', ['id' => $agente->id]);
        $this->apiGet('/api/v1/agentes/'.$agente->id, $administrator)->assertNotFound();
    }

    public function test_agent_can_only_list_and_view_own_profile(): void
    {
        $agentUser = $this->user('agent-own@example.test', 'Agente Inmobiliario');
        $otherAgentUser = $this->user('agent-other@example.test', 'Agente Inmobiliario');
        $own = $this->agent($agentUser, 'AG-OWN');
        $other = $this->agent($otherAgentUser, 'AG-OTHER');

        $this->apiGet('/api/v1/agentes', $agentUser)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);

        $this->apiGet('/api/v1/agentes/'.$own->id, $agentUser)->assertOk();
        $this->apiGet('/api/v1/agentes/'.$other->id, $agentUser)->assertForbidden();

        $this->apiPost('/api/v1/agentes', $agentUser, [
            'user_id' => $this->user('agent-create-forbidden@example.test', 'Agente Inmobiliario')->id,
            'numero_empleado' => 'AG-FORBIDDEN',
        ])->assertForbidden();
        $this->apiPatch('/api/v1/agentes/'.$own->id, $agentUser, [
            'numero_empleado' => 'AG-NEW',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/agentes/'.$own->id, $agentUser)->assertForbidden();
    }

    public function test_agent_search_cannot_reveal_another_profile(): void
    {
        $admin = $this->user('admin-agent-search@example.test', 'Administrador');
        $agent = $this->user('agent-search@example.test', 'Agente Inmobiliario');
        $other = $this->user('other-search@example.test', 'Agente Inmobiliario');
        $own = $this->agent($agent, 'SEARCH-OWN');
        $this->agent($other, 'SEARCH-OTHER');

        $this->apiGet('/api/v1/agentes?q=SEARCH-OTHER', $agent)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/agentes?q=SEARCH-OTHER', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/agentes?q='.$agent->nombres, $agent)
            ->assertOk()
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_assistant_and_director_can_read_but_not_write_agents(): void
    {
        $agent = $this->agent(
            $this->user('agent-readable@example.test', 'Agente Inmobiliario'),
            'AG-READ'
        );

        foreach (['Asistente', 'Director General'] as $role) {
            $user = $this->user(strtolower(str_replace(' ', '-', $role)).'@example.test', $role);

            $this->apiGet('/api/v1/agentes', $user)->assertOk();
            $this->apiGet('/api/v1/agentes/'.$agent->id, $user)->assertOk();
            $this->apiPost('/api/v1/agentes', $user, [
                'user_id' => $this->user('agent-'.$role.'-create@example.test', 'Agente Inmobiliario')->id,
                'numero_empleado' => 'AG-'.strtoupper(substr(md5($role), 0, 6)),
            ])->assertForbidden();
            $this->apiPatch('/api/v1/agentes/'.$agent->id, $user, [
                'numero_empleado' => 'AG-NO',
            ])->assertForbidden();
            $this->apiDelete('/api/v1/agentes/'.$agent->id, $user)->assertForbidden();
        }
    }

    public function test_client_cannot_access_agents(): void
    {
        $client = $this->user('client-agents@example.test', 'Cliente');

        $this->apiGet('/api/v1/agentes', $client)->assertForbidden();
    }

    public function test_create_requires_existing_non_deleted_user_with_agent_role(): void
    {
        $admin = $this->user('admin-agent-users@example.test', 'Administrador');
        $withoutRole = $this->user('without-agent-role@example.test');
        $softDeleted = $this->user('soft-deleted-agent-user@example.test', 'Agente Inmobiliario');
        $softDeleted->delete();

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $withoutRole->id,
            'numero_empleado' => 'AG-NO-ROLE',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $softDeleted->id,
            'numero_empleado' => 'AG-SOFT-USER',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => 999999,
            'numero_empleado' => 'AG-MISSING-USER',
        ])->assertUnprocessable();
    }

    public function test_user_cannot_be_reused_by_active_or_soft_deleted_agent(): void
    {
        $admin = $this->user('admin-agent-unique@example.test', 'Administrador');
        $user = $this->user('unique-agent-user@example.test', 'Agente Inmobiliario');
        $existing = $this->agent($user, 'AG-EXISTING');

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $user->id,
            'numero_empleado' => 'AG-DUPLICATE-USER',
        ])->assertUnprocessable();

        $existing->delete();

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $user->id,
            'numero_empleado' => 'AG-SOFT-DUPLICATE-USER',
        ])->assertUnprocessable();
    }

    public function test_employee_number_remains_reserved_by_soft_deleted_agent(): void
    {
        $admin = $this->user('admin-agent-number@example.test', 'Administrador');
        $firstUser = $this->user('first-number-agent@example.test', 'Agente Inmobiliario');
        $secondUser = $this->user('second-number-agent@example.test', 'Agente Inmobiliario');
        $agent = $this->agent($firstUser, 'AG-RESERVED');
        $agent->delete();

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $secondUser->id,
            'numero_empleado' => 'AG-RESERVED',
        ])->assertUnprocessable();
    }

    public function test_update_cannot_change_user_or_roles(): void
    {
        $admin = $this->user('admin-agent-security@example.test', 'Administrador');
        $user = $this->user('agent-security@example.test', 'Agente Inmobiliario');
        $otherUser = $this->user('other-agent-security@example.test', 'Agente Inmobiliario');
        $agent = $this->agent($user, 'AG-SECURITY');

        foreach (['user_id', 'roles', 'permisos', 'foto_path', 'inmuebles', 'clientes', 'operaciones', 'asignaciones'] as $field) {
            $this->apiPatch('/api/v1/agentes/'.$agent->id, $admin, [
                $field => $field === 'user_id' ? $otherUser->id : ['forbidden'],
            ])->assertUnprocessable();
        }

        self::assertTrue($user->fresh()->hasRole('Agente Inmobiliario'));
        self::assertFalse($user->fresh()->hasRole('Administrador'));
        self::assertSame($user->id, $agent->fresh()->user_id);
    }

    public function test_commission_and_state_defaults_and_validation(): void
    {
        $admin = $this->user('admin-agent-validation@example.test', 'Administrador');
        $defaultUser = $this->user('agent-defaults@example.test', 'Agente Inmobiliario');

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $defaultUser->id,
            'numero_empleado' => 'AG-DEFAULTS',
        ])->assertCreated()
            ->assertJsonPath('data.porcentaje_comision', '0.00')
            ->assertJsonPath('data.estado_laboral', 'activo');

        $validZero = $this->user('agent-zero@example.test', 'Agente Inmobiliario');
        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $validZero->id,
            'numero_empleado' => 'AG-ZERO',
            'porcentaje_comision' => '0.00',
            'estado_laboral' => 'inactivo',
        ])->assertCreated();

        foreach ([-0.01, 100.01, '12.345', null] as $index => $commission) {
            $user = $this->user('agent-invalid-commission-'.$index.'@example.test', 'Agente Inmobiliario');
            $this->apiPost('/api/v1/agentes', $admin, [
                'user_id' => $user->id,
                'numero_empleado' => 'AG-BAD-'.$index,
                'porcentaje_comision' => $commission,
            ])->assertUnprocessable();
        }

        $nullStateUser = $this->user('agent-null-state@example.test', 'Agente Inmobiliario');
        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $nullStateUser->id,
            'numero_empleado' => 'AG-NULL-STATE',
            'estado_laboral' => null,
        ])->assertUnprocessable();
    }

    public function test_index_filters_paginates_and_sorts_agents(): void
    {
        $admin = $this->user('admin-agent-index@example.test', 'Administrador');
        $first = $this->agent(
            $this->user('ana-agent-index@example.test', 'Agente Inmobiliario', 'Ana'),
            'AG-INDEX-A'
        );
        $second = $this->agent(
            $this->user('bruno-agent-index@example.test', 'Agente Inmobiliario', 'Bruno'),
            'AG-INDEX-B'
        );
        $second->update(['estado_laboral' => 'inactivo']);

        $this->apiGet('/api/v1/agentes?q=Ana', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $first->id);
        $this->apiGet('/api/v1/agentes?estado_laboral=inactivo', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $second->id);
        $this->apiGet('/api/v1/agentes?sort=numero_empleado&direction=asc', $admin)->assertOk();
        $this->apiGet('/api/v1/agentes?sort=arbitrary', $admin)->assertUnprocessable();
        $this->apiGet('/api/v1/agentes?direction=random', $admin)->assertUnprocessable();
    }

    public function test_index_pagination_limits_are_enforced(): void
    {
        $admin = $this->user('admin-agent-pagination@example.test', 'Administrador');

        for ($index = 0; $index < 16; $index++) {
            $this->agent(
                $this->user('pagination-agent-'.$index.'@example.test', 'Agente Inmobiliario'),
                'AG-PAGE-'.$index
            );
        }

        $this->apiGet('/api/v1/agentes', $admin)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16);
        $this->apiGet('/api/v1/agentes?per_page=100', $admin)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
        $this->apiGet('/api/v1/agentes?per_page=101', $admin)->assertUnprocessable();
    }

    public function test_validation_rejects_lengths_and_invalid_date(): void
    {
        $admin = $this->user('admin-agent-fields@example.test', 'Administrador');
        $user = $this->user('agent-fields@example.test', 'Agente Inmobiliario');

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $user->id,
            'numero_empleado' => str_repeat('A', 31),
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/agentes', $admin, [
            'user_id' => $user->id,
            'numero_empleado' => 'AG-FIELDS',
            'telefono_corporativo' => str_repeat('5', 21),
            'zona_asignacion' => str_repeat('Z', 101),
            'fecha_contratacion' => '25-09-2026',
        ])->assertUnprocessable();
    }

    public function test_delete_preserves_user_role_and_existing_relations(): void
    {
        $admin = $this->user('admin-agent-delete-relations@example.test', 'Administrador');
        $agentUser = $this->user('agent-delete-relations@example.test', 'Agente Inmobiliario');
        $agent = $this->agent($agentUser, 'AG-RELATIONS');
        $inmueble = $this->property('agent-delete-relations');
        $assignment = AgenteInmueble::create([
            'agente_id' => $agent->id,
            'inmueble_id' => $inmueble->id,
        ]);

        $this->apiDelete('/api/v1/agentes/'.$agent->id, $admin)->assertNoContent();

        self::assertSoftDeleted('agentes', ['id' => $agent->id]);
        self::assertDatabaseHas('users', ['id' => $agentUser->id, 'deleted_at' => null]);
        self::assertTrue($agentUser->fresh()->hasRole('Agente Inmobiliario'));
        self::assertDatabaseHas('agente_inmueble', ['id' => $assignment->id, 'agente_id' => $agent->id]);
    }

    private function user(string $email, ?string $role = null, string $name = 'Usuario'): User
    {
        $user = User::create([
            'nombres' => $name,
            'apellido_paterno' => 'Agente',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    private function agent(User $user, string $numeroEmpleado): Agente
    {
        return Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => $numeroEmpleado,
        ]);
    }

    private function property(string $suffix): Inmueble
    {
        $owner = Propietario::create([
            'nombre_razon_social' => 'Owner '.$suffix,
            'rfc' => strtoupper(substr(md5('owner-'.$suffix), 0, 13)),
            'telefono' => '5555555555',
            'direccion' => 'Calle 1',
        ]);
        $category = Categoria::create(['nombre' => 'Category '.$suffix]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'AG-'.$suffix,
            'titulo' => 'Inmueble '.$suffix,
            'slug' => 'inmueble-agente-'.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
        ]);
    }

    private function apiGet(string $uri, User $user)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }

    /** @param array<string, mixed> $payload */
    private function apiPost(string $uri, User $user, array $payload)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson($uri, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function apiPatch(string $uri, User $user, array $payload)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson($uri, $payload);
    }

    private function apiDelete(string $uri, User $user)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson($uri);
    }
}
