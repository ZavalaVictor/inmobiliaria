<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_clients(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/clientes')
            ->assertUnauthorized();
    }

    public function test_administrator_can_list_all_clients(): void
    {
        $administrator = $this->user('admin-list@example.test', 'Administrador');
        $this->client('Ana', 'admin-list-a@example.test');
        $this->client('Bruno', 'admin-list-b@example.test');

        $response = $this->apiGet('/api/v1/clientes', $administrator);

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_administrator_can_create_show_update_and_delete_a_client(): void
    {
        $administrator = $this->user('admin-crud@example.test', 'Administrador');
        $payload = [
            'nombres' => 'Carlos',
            'apellido_paterno' => 'Administrador',
            'email' => 'carlos@example.test',
            'telefono' => '5555555555',
            'tipo_interes' => 'compra',
            'presupuesto_min' => '100000.00',
            'presupuesto_max' => '250000.00',
            'preferencias' => 'Casa con jardín',
            'estado_cliente' => 'prospecto',
        ];

        $created = $this->apiPost('/api/v1/clientes', $administrator, $payload)
            ->assertCreated()
            ->assertJsonPath('data.nombres', 'Carlos')
            ->assertJsonPath('data.tipo_interes', 'compra')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');

        $cliente = Cliente::query()->where('email', 'carlos@example.test')->firstOrFail();
        self::assertNull($cliente->user_id);
        self::assertSame(0, $cliente->interesesInmuebles()->count());

        $this->apiGet('/api/v1/clientes/'.$cliente->id, $administrator)
            ->assertOk()
            ->assertJsonPath('data.id', $created->json('data.id'));

        $this->apiPatch('/api/v1/clientes/'.$cliente->id, $administrator, [
            'nombres' => 'Carlos Actualizado',
        ])->assertOk()->assertJsonPath('data.nombres', 'Carlos Actualizado');

        $this->apiDelete('/api/v1/clientes/'.$cliente->id, $administrator)
            ->assertNoContent();

        $this->apiGet('/api/v1/clientes/'.$cliente->id, $administrator)
            ->assertNotFound();
    }

    public function test_agent_lists_only_assigned_clients_and_cannot_access_unassigned_clients(): void
    {
        $agent = $this->agentUser('agent-list@example.test');
        $assigned = $this->client('Asignado', 'assigned@example.test');
        $unassigned = $this->client('No asignado', 'unassigned@example.test');
        $this->assignClient($assigned, $agent->agente);

        $this->apiGet('/api/v1/clientes', $agent)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assigned->id);

        $this->apiGet('/api/v1/clientes/'.$assigned->id, $agent)->assertOk();
        $this->apiGet('/api/v1/clientes/'.$unassigned->id, $agent)->assertForbidden();
    }

    public function test_agent_can_update_assigned_client_but_not_unassigned_client_or_delete(): void
    {
        $agent = $this->agentUser('agent-update@example.test');
        $assigned = $this->client('Asignado', 'agent-update-assigned@example.test');
        $unassigned = $this->client('No asignado', 'agent-update-unassigned@example.test');
        $this->assignClient($assigned, $agent->agente);

        $this->apiPatch('/api/v1/clientes/'.$assigned->id, $agent, [
            'nombres' => 'Actualizado por agente',
        ])->assertOk();

        $this->apiPatch('/api/v1/clientes/'.$unassigned->id, $agent, [
            'nombres' => 'No permitido',
        ])->assertForbidden();

        $this->apiDelete('/api/v1/clientes/'.$assigned->id, $agent)
            ->assertForbidden();
    }

    public function test_agent_create_assigns_new_client_as_principal(): void
    {
        $agent = $this->agentUser('agent-create@example.test');

        $this->apiPost('/api/v1/clientes', $agent, [
            'nombres' => 'Prospecto',
            'apellido_paterno' => 'Agente',
        ])->assertCreated();

        $cliente = Cliente::query()->where('email', null)->latest('id')->firstOrFail();
        $assignment = ClienteAgente::query()
            ->where('cliente_id', $cliente->id)
            ->where('agente_id', $agent->agente->id)
            ->first();

        self::assertNotNull($assignment);
        self::assertTrue($assignment->es_principal);
        self::assertSame(1, $cliente->asignacionesAgentes()->count());
    }

    public function test_agent_without_agent_profile_cannot_create_orphan_client(): void
    {
        $agent = $this->user('orphan-agent-create@example.test', 'Agente Inmobiliario');

        $this->apiPost('/api/v1/clientes', $agent, [
            'nombres' => 'No debe',
            'apellido_paterno' => 'Crearse',
        ])->assertForbidden();

        self::assertDatabaseMissing('clientes', ['nombres' => 'No debe']);
    }

    public function test_agent_cannot_force_assignment_to_another_agent(): void
    {
        $agent = $this->agentUser('agent-forced-assignment@example.test');
        $otherAgent = $this->agentUser('other-forced-assignment@example.test');

        $this->apiPost('/api/v1/clientes', $agent, [
            'nombres' => 'Asignación forzada',
            'apellido_paterno' => 'No permitida',
            'agente_id' => $otherAgent->agente->id,
        ])->assertUnprocessable();

        self::assertDatabaseMissing('clientes', ['nombres' => 'Asignación forzada']);
        self::assertDatabaseMissing('cliente_agente', ['agente_id' => $otherAgent->agente->id]);
    }

    public function test_assistant_can_manage_clients_but_does_not_create_assignment(): void
    {
        $assistant = $this->user('assistant-clientes@example.test', 'Asistente');

        $this->apiGet('/api/v1/clientes', $assistant)->assertOk();
        $this->apiPost('/api/v1/clientes', $assistant, [
            'nombres' => 'Creado por asistente',
            'apellido_paterno' => 'Prueba',
        ])->assertCreated();

        $cliente = Cliente::query()->where('nombres', 'Creado por asistente')->firstOrFail();
        self::assertSame(0, $cliente->asignacionesAgentes()->count());

        $this->apiPatch('/api/v1/clientes/'.$cliente->id, $assistant, [
            'estado_cliente' => 'cliente',
        ])->assertOk();

        $this->apiDelete('/api/v1/clientes/'.$cliente->id, $assistant)
            ->assertForbidden();
    }

    public function test_director_can_only_read_clients(): void
    {
        $director = $this->user('director-clientes@example.test', 'Director General');
        $cliente = $this->client('Director', 'director-client@example.test');

        $this->apiGet('/api/v1/clientes', $director)->assertOk();
        $this->apiGet('/api/v1/clientes/'.$cliente->id, $director)->assertOk();
        $this->apiPost('/api/v1/clientes', $director, [
            'nombres' => 'No permitido',
            'apellido_paterno' => 'Director',
        ])->assertForbidden();
        $this->apiPatch('/api/v1/clientes/'.$cliente->id, $director, [
            'nombres' => 'No permitido',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/clientes/'.$cliente->id, $director)->assertForbidden();
    }

    public function test_client_can_only_list_show_and_update_own_profile(): void
    {
        $clientUser = $this->user('portal-client@example.test', 'Cliente');
        $client = $this->client('Portal', 'portal-client@example.test', $clientUser);
        $other = $this->client('Otro', 'other-portal@example.test');

        $this->apiGet('/api/v1/clientes', $clientUser)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $client->id);
        $this->apiGet('/api/v1/clientes/'.$client->id, $clientUser)->assertOk();
        $this->apiGet('/api/v1/clientes/'.$other->id, $clientUser)->assertForbidden();

        $this->apiPatch('/api/v1/clientes/'.$client->id, $clientUser, [
            'nombres' => 'Portal actualizado',
            'telefono' => '5550000000',
        ])->assertOk();

        $this->apiDelete('/api/v1/clientes/'.$client->id, $clientUser)->assertForbidden();
    }

    public function test_client_cannot_update_email_state_user_or_privileged_fields(): void
    {
        $clientUser = $this->user('portal-protected@example.test', 'Cliente');
        $client = $this->client('Portal', 'portal-protected@example.test', $clientUser);

        foreach ([
            ['email' => 'changed@example.test'],
            ['estado_cliente' => 'cliente'],
            ['user_id' => 999999],
            ['roles' => ['Administrador']],
            ['permisos' => ['clientes.eliminar']],
            ['agentes' => [1]],
            ['asignaciones' => [1]],
        ] as $payload) {
            $this->apiPatch('/api/v1/clientes/'.$client->id, $clientUser, $payload)
                ->assertUnprocessable();
        }
    }

    public function test_update_rejects_all_administrative_prohibited_fields(): void
    {
        $administrator = $this->user('admin-update-prohibited@example.test', 'Administrador');
        $cliente = $this->client('Update protegido', 'update-protected@example.test');

        foreach ([
            'id' => 999999,
            'user_id' => 999999,
            'created_at' => '2026-09-25 00:00:00',
            'updated_at' => '2026-09-25 00:00:00',
            'deleted_at' => '2026-09-25 00:00:00',
            'roles' => ['Administrador'],
            'permisos' => ['clientes.eliminar'],
            'agentes' => [1],
            'asignaciones' => [1],
        ] as $field => $value) {
            $this->apiPatch('/api/v1/clientes/'.$cliente->id, $administrator, [
                $field => $value,
            ])->assertUnprocessable();
        }
    }

    public function test_validation_rejects_invalid_enum_email_phone_and_budget_range(): void
    {
        $administrator = $this->user('admin-validation@example.test', 'Administrador');

        $this->apiPost('/api/v1/clientes', $administrator, [
            'nombres' => 'Inválido',
            'apellido_paterno' => 'Prueba',
            'tipo_interes' => 'invalid',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/clientes', $administrator, [
            'nombres' => 'Inválido',
            'apellido_paterno' => 'Prueba',
            'estado_cliente' => 'invalid',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/clientes', $administrator, [
            'nombres' => 'Inválido',
            'apellido_paterno' => 'Prueba',
            'email' => 'not-an-email',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/clientes', $administrator, [
            'nombres' => 'Inválido',
            'apellido_paterno' => 'Prueba',
            'telefono' => str_repeat('5', 21),
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/clientes', $administrator, [
            'nombres' => 'Inválido',
            'apellido_paterno' => 'Prueba',
            'presupuesto_min' => 200000,
            'presupuesto_max' => 100000,
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/clientes', $administrator, [
            'nombres' => 'Inválido',
            'apellido_paterno' => 'Prueba',
            'presupuesto_min' => -1,
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/clientes', $administrator, [
            'nombres' => 'Inválido',
            'apellido_paterno' => 'Prueba',
            'presupuesto_max' => '1000000000000.00',
        ])->assertUnprocessable();
    }

    public function test_create_uses_database_default_for_estado_cliente(): void
    {
        $administrator = $this->user('admin-default-state@example.test', 'Administrador');

        $this->apiPost('/api/v1/clientes', $administrator, [
            'nombres' => 'Estado por defecto',
            'apellido_paterno' => 'Prueba',
        ])->assertCreated();

        $cliente = Cliente::query()
            ->where('nombres', 'Estado por defecto')
            ->firstOrFail();

        self::assertSame('prospecto', $cliente->getRawOriginal('estado_cliente'));
    }

    public function test_partial_budget_updates_preserve_the_budget_range_constraint(): void
    {
        $administrator = $this->user('admin-budget-patch@example.test', 'Administrador');
        $cliente = $this->client('Presupuesto parcial', 'budget-patch@example.test');
        $cliente->update([
            'presupuesto_min' => '100.00',
            'presupuesto_max' => '200.00',
        ]);

        $this->apiPatch('/api/v1/clientes/'.$cliente->id, $administrator, [
            'presupuesto_max' => '50.00',
        ])->assertUnprocessable();

        $this->apiPatch('/api/v1/clientes/'.$cliente->id, $administrator, [
            'presupuesto_min' => '300.00',
        ])->assertUnprocessable();

        $this->apiPatch('/api/v1/clientes/'.$cliente->id, $administrator, [
            'presupuesto_max' => '1000000000000.00',
        ])->assertUnprocessable();
    }

    public function test_create_rejects_sensitive_fields_and_does_not_create_user(): void
    {
        $administrator = $this->user('admin-sensitive@example.test', 'Administrador');

        foreach (['id', 'user_id', 'agente_id', 'agentes', 'asignaciones', 'roles', 'permisos', 'created_at', 'updated_at', 'deleted_at'] as $field) {
            $this->apiPost('/api/v1/clientes', $administrator, [
                'nombres' => 'Campo prohibido',
                'apellido_paterno' => 'Prueba',
                $field => $field === 'id' ? 999999 : ['valor'],
            ])->assertUnprocessable();
        }
    }

    public function test_index_supports_search_enums_agent_filter_pagination_and_sort_whitelist(): void
    {
        $administrator = $this->user('admin-filters@example.test', 'Administrador');
        $agent = $this->agentUser('filter-agent@example.test');
        $assigned = $this->client('Filtro Especial', 'filter-assigned@example.test', null, 'renta');
        $other = $this->client('Otro Cliente', 'filter-other@example.test', null, 'compra');
        $this->assignClient($assigned, $agent->agente);

        $this->apiGet('/api/v1/clientes?q=Especial', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/clientes?estado_cliente=prospecto', $administrator)->assertOk();
        $this->apiGet('/api/v1/clientes?tipo_interes=renta', $administrator)
            ->assertOk()
            ->assertJsonFragment(['id' => $assigned->id]);
        $this->apiGet('/api/v1/clientes?agente_id='.$agent->agente->id, $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/clientes?per_page=101', $administrator)->assertUnprocessable();
        $this->apiGet('/api/v1/clientes?sort=not_a_column', $administrator)->assertUnprocessable();
        $this->apiGet('/api/v1/clientes?direction=random', $administrator)->assertUnprocessable();
        $this->apiGet('/api/v1/clientes?sort=nombres&direction=asc', $administrator)->assertOk();

        $this->apiGet('/api/v1/clientes?agente_id='.$agent->agente->id, $agent)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assigned->id);
        self::assertNotSame($assigned->id, $other->id);
    }

    public function test_index_uses_default_and_maximum_pagination_sizes(): void
    {
        $administrator = $this->user('admin-pagination@example.test', 'Administrador');

        for ($index = 1; $index <= 16; $index++) {
            $this->client('Paginación '.$index, 'pagination-'.$index.'@example.test');
        }

        $this->apiGet('/api/v1/clientes', $administrator)
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2);

        $this->apiGet('/api/v1/clientes?per_page=100', $administrator)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.total', 16);
    }

    public function test_search_cannot_escape_agent_visibility_scope(): void
    {
        $agentA = $this->agentUser('agent-search-a@example.test');
        $agentB = $this->agentUser('agent-search-b@example.test');
        $clientA = $this->client('Cliente A', 'client-a-search@example.test');
        $clientB = $this->client('Coincidencia SoloB', 'client-b-search@example.test');
        $this->assignClient($clientA, $agentA->agente);
        $this->assignClient($clientB, $agentB->agente);

        $this->apiGet('/api/v1/clientes?q=SoloB', $agentA)
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonMissing(['id' => $clientB->id]);
    }

    public function test_soft_deleted_clients_are_not_listed_or_resolved(): void
    {
        $administrator = $this->user('admin-soft-delete@example.test', 'Administrador');
        $cliente = $this->client('Eliminado', 'soft-delete@example.test');
        $cliente->delete();

        $this->apiGet('/api/v1/clientes', $administrator)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/clientes/'.$cliente->id, $administrator)->assertNotFound();
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Prueba',
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
        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => strtoupper(substr(md5($email), 0, 10)),
        ]);

        return $user->fresh(['agente']);
    }

    private function client(
        string $name,
        string $email,
        ?User $user = null,
        ?string $interestType = null
    ): Cliente {
        return Cliente::create([
            'user_id' => $user?->id,
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => $email,
            'tipo_interes' => $interestType,
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function assignClient(Cliente $cliente, Agente $agent): ClienteAgente
    {
        return ClienteAgente::create([
            'cliente_id' => $cliente->id,
            'agente_id' => $agent->id,
            'es_principal' => false,
            'fecha_asignacion' => now(),
        ]);
    }

    private function apiGet(string $uri, User $user): TestResponse
    {
        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }

    /** @param array<string, mixed> $payload */
    private function apiPost(string $uri, User $user, array $payload): TestResponse
    {
        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson($uri, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function apiPatch(string $uri, User $user, array $payload): TestResponse
    {
        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson($uri, $payload);
    }

    private function apiDelete(string $uri, User $user): TestResponse
    {
        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson($uri);
    }
}
