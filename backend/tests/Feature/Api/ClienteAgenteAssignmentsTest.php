<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ClienteAgenteAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_assignments(): void
    {
        $cliente = $this->client('No autenticado');

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson($this->assignmentUrl($cliente))
            ->assertUnauthorized();
    }

    public function test_administrator_can_list_assign_set_principal_and_delete_assignments(): void
    {
        $admin = $this->user('admin-client-agent@example.test', 'Administrador');
        $cliente = $this->client('Administrado');
        $first = $this->agentUser('client-agent-first@example.test');
        $second = $this->agentUser('client-agent-second@example.test');

        $createdFirst = $this->apiPost($this->assignmentUrl($cliente), $admin, [
            'agente_id' => $first->agente->id,
        ])->assertCreated()
            ->assertJsonPath('data.es_principal', true)
            ->assertJsonMissingPath('data.agente.user.password');
        $firstAssignment = ClienteAgente::query()->findOrFail($createdFirst->json('data.id'));
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'cliente_agente_asignado',
            'entidad' => 'cliente_agente',
            'entidad_id' => $firstAssignment->id,
            'user_id' => $admin->id,
        ]);

        $createdSecond = $this->apiPost($this->assignmentUrl($cliente), $admin, [
            'agente_id' => $second->agente->id,
        ])->assertCreated()
            ->assertJsonPath('data.es_principal', false);
        $secondAssignment = ClienteAgente::query()->findOrFail($createdSecond->json('data.id'));

        $this->apiGet($this->assignmentUrl($cliente), $admin)
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->apiPatch($this->principalUrl($cliente, $secondAssignment), $admin, [])
            ->assertOk()
            ->assertJsonPath('data.es_principal', true);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'cliente_agente_principal_cambiado',
            'entidad_id' => $secondAssignment->id,
            'user_id' => $admin->id,
        ]);

        self::assertFalse((bool) $firstAssignment->fresh()->es_principal);
        $this->apiDelete($this->assignmentUrl($cliente).'/'.$secondAssignment->id, $admin)
            ->assertNoContent();
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'cliente_agente_desasignado',
            'entidad_id' => $secondAssignment->id,
            'user_id' => $admin->id,
        ]);
        self::assertTrue((bool) $firstAssignment->fresh()->es_principal);

        $this->apiDelete($this->assignmentUrl($cliente).'/'.$firstAssignment->id, $admin)
            ->assertNoContent();
        self::assertDatabaseCount('cliente_agente', 0);
        self::assertDatabaseHas('clientes', ['id' => $cliente->id]);
        self::assertDatabaseHas('agentes', ['id' => $first->agente->id]);
    }

    public function test_assistant_can_assign_and_change_principal_but_cannot_delete(): void
    {
        $assistant = $this->user('assistant-client-agent@example.test', 'Asistente');
        $cliente = $this->client('Asistente');
        $first = $this->agentUser('assistant-client-agent-first@example.test');
        $second = $this->agentUser('assistant-client-agent-second@example.test');

        $this->apiPost($this->assignmentUrl($cliente), $assistant, ['agente_id' => $first->agente->id])
            ->assertCreated();
        $secondResponse = $this->apiPost($this->assignmentUrl($cliente), $assistant, ['agente_id' => $second->agente->id])
            ->assertCreated();
        $secondAssignment = ClienteAgente::query()->findOrFail($secondResponse->json('data.id'));

        $this->apiPatch($this->principalUrl($cliente, $secondAssignment), $assistant, [])
            ->assertOk();
        $this->apiDelete($this->assignmentUrl($cliente).'/'.$secondAssignment->id, $assistant)
            ->assertForbidden();
    }

    public function test_director_can_only_list_and_agent_only_sees_own_assignment(): void
    {
        $director = $this->user('director-client-agent@example.test', 'Director General');
        $admin = $this->user('scope-client-agent-admin@example.test', 'Administrador');
        $agent = $this->agentUser('scope-client-agent@example.test');
        $other = $this->agentUser('scope-client-agent-other@example.test');
        $cliente = $this->client('Alcance');

        $own = $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $agent->agente->id])
            ->assertCreated();
        $otherAssignment = $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $other->agente->id])
            ->assertCreated();

        $this->apiGet($this->assignmentUrl($cliente), $director)
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->apiGet($this->assignmentUrl($cliente), $agent)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.agente_id', $agent->agente->id);
        $this->apiPost($this->assignmentUrl($cliente), $agent, ['agente_id' => $this->agentUser('third-client-agent@example.test')->agente->id])
            ->assertForbidden();

        $ownAssignment = ClienteAgente::query()->findOrFail($own->json('data.id'));
        $otherModel = ClienteAgente::query()->findOrFail($otherAssignment->json('data.id'));
        $this->apiPatch($this->principalUrl($cliente, $ownAssignment), $agent, [])->assertForbidden();
        $this->apiDelete($this->assignmentUrl($cliente).'/'.$otherModel->id, $agent)->assertForbidden();
    }

    public function test_client_can_only_list_assignments_of_own_profile(): void
    {
        $clientUser = $this->user('portal-client-agent@example.test', 'Cliente');
        $ownClient = $this->client('Propio', $clientUser);
        $otherClient = $this->client('Ajeno');
        $agent = $this->agentUser('portal-client-agent-agent@example.test');

        ClienteAgente::create(['cliente_id' => $ownClient->id, 'agente_id' => $agent->agente->id, 'es_principal' => true]);

        $this->apiGet($this->assignmentUrl($ownClient), $clientUser)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet($this->assignmentUrl($otherClient), $clientUser)->assertForbidden();
        $otherAgent = $this->agentUser('portal-client-agent-other@example.test');
        $this->apiPost($this->assignmentUrl($ownClient), $clientUser, ['agente_id' => $otherAgent->agente->id])
            ->assertForbidden();
    }

    public function test_first_assignment_is_principal_even_when_omitted_or_false(): void
    {
        foreach ([[], ['es_principal' => false], ['es_principal' => true]] as $index => $payload) {
            $admin = $this->user('first-client-agent-'.$index.'@example.test', 'Administrador');
            $cliente = $this->client('Primero '.$index);
            $agent = $this->agentUser('first-client-agent-'.$index.'-agent@example.test');

            $this->apiPost($this->assignmentUrl($cliente), $admin, array_merge([
                'agente_id' => $agent->agente->id,
            ], $payload))
                ->assertCreated()
                ->assertJsonPath('data.es_principal', true);
        }
    }

    public function test_second_assignment_and_principal_switch_keep_exactly_one_principal(): void
    {
        $admin = $this->user('switch-client-agent@example.test', 'Administrador');
        $cliente = $this->client('Cambio principal');
        $first = $this->agentUser('switch-client-agent-first@example.test');
        $second = $this->agentUser('switch-client-agent-second@example.test');

        $firstResponse = $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $first->agente->id])->assertCreated();
        $secondResponse = $this->apiPost($this->assignmentUrl($cliente), $admin, [
            'agente_id' => $second->agente->id,
            'es_principal' => true,
        ])->assertCreated()->assertJsonPath('data.es_principal', true);

        self::assertFalse((bool) ClienteAgente::query()->findOrFail($firstResponse->json('data.id'))->es_principal);
        self::assertSame(1, ClienteAgente::query()->where('cliente_id', $cliente->id)->where('es_principal', true)->count());

        $secondAssignment = ClienteAgente::query()->findOrFail($secondResponse->json('data.id'));
        $this->apiPatch($this->principalUrl($cliente, $secondAssignment), $admin, [])->assertOk();
        self::assertSame(2, ClienteAgente::query()->where('cliente_id', $cliente->id)->count());
    }

    public function test_duplicate_soft_deleted_and_prohibited_payloads_are_rejected(): void
    {
        $admin = $this->user('validation-client-agent@example.test', 'Administrador');
        $cliente = $this->client('Validación');
        $agent = $this->agentUser('validation-client-agent-agent@example.test');

        $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $agent->agente->id])->assertCreated();
        $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $agent->agente->id])->assertUnprocessable();

        $deletedAgent = $this->agentUser('deleted-client-agent@example.test');
        $deletedAgent->agente->delete();
        $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $deletedAgent->agente->id])->assertUnprocessable();

        foreach (['cliente_id', 'fecha_asignacion', 'id', 'created_at', 'updated_at', 'roles', 'permisos', 'cliente', 'agente'] as $field) {
            $this->apiPost($this->assignmentUrl($cliente), $admin, [
                'agente_id' => $this->agentUser('prohibited-'.$field.'@example.test')->agente->id,
                $field => $field === 'id' ? 1 : ['forbidden'],
            ])->assertUnprocessable();
        }
    }

    public function test_inactive_agent_is_eligible_but_soft_deleted_agent_is_not(): void
    {
        $admin = $this->user('inactive-client-agent@example.test', 'Administrador');
        $cliente = $this->client('Inactivo');
        $inactive = $this->agentUser('inactive-client-agent-agent@example.test', 'inactivo');
        $second = $this->agentUser('inactive-client-agent-second@example.test');

        $response = $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $inactive->agente->id]);
        $response->assertCreated();
        $inactiveAssignment = ClienteAgente::query()->findOrFail($response->json('data.id'));
        self::assertTrue((bool) $inactiveAssignment->es_principal);

        $inactive->agente->delete();
        $this->apiGet($this->assignmentUrl($cliente), $admin)->assertOk()->assertJsonCount(0, 'data');

        $newResponse = $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $second->agente->id, 'es_principal' => false])
            ->assertCreated()
            ->assertJsonPath('data.es_principal', true);
        self::assertFalse((bool) $inactiveAssignment->fresh()->es_principal);
        self::assertTrue((bool) ClienteAgente::query()->findOrFail($newResponse->json('data.id'))->es_principal);
    }

    public function test_delete_principal_promotes_only_eligible_agent_and_last_delete_leaves_none(): void
    {
        $admin = $this->user('promote-client-agent@example.test', 'Administrador');
        $cliente = $this->client('Promoción');
        $deleted = $this->agentUser('promote-deleted@example.test');
        $next = $this->agentUser('promote-next@example.test');
        $last = $this->agentUser('promote-last@example.test');

        $deletedAssignment = ClienteAgente::create([
            'cliente_id' => $cliente->id,
            'agente_id' => $deleted->agente->id,
            'es_principal' => true,
            'fecha_asignacion' => '2026-01-01 10:00:00',
        ]);
        $nextAssignment = ClienteAgente::create([
            'cliente_id' => $cliente->id,
            'agente_id' => $next->agente->id,
            'es_principal' => true,
            'fecha_asignacion' => '2026-01-02 10:00:00',
        ]);
        $lastAssignment = ClienteAgente::create([
            'cliente_id' => $cliente->id,
            'agente_id' => $last->agente->id,
            'es_principal' => false,
            'fecha_asignacion' => '2026-01-03 10:00:00',
        ]);
        $deleted->agente->delete();

        $this->apiDelete($this->assignmentUrl($cliente).'/'.$nextAssignment->id, $admin)->assertNoContent();
        self::assertTrue((bool) $lastAssignment->fresh()->es_principal);
        self::assertFalse((bool) $deletedAssignment->fresh()->es_principal);

        $this->apiDelete($this->assignmentUrl($cliente).'/'.$lastAssignment->id, $admin)->assertNoContent();
        self::assertSame(1, ClienteAgente::query()->where('cliente_id', $cliente->id)->count());
        self::assertFalse((bool) $deletedAssignment->fresh()->es_principal);
    }

    public function test_scoped_binding_rejects_other_client_missing_soft_deleted_client_and_agent(): void
    {
        $admin = $this->user('scoped-client-agent@example.test', 'Administrador');
        $clientA = $this->client('Cliente A');
        $clientB = $this->client('Cliente B');
        $agent = $this->agentUser('scoped-client-agent-agent@example.test');
        $assignment = ClienteAgente::create(['cliente_id' => $clientB->id, 'agente_id' => $agent->agente->id, 'es_principal' => true]);

        $this->apiPatch($this->principalUrl($clientA, $assignment), $admin, [])->assertNotFound();
        $this->apiDelete($this->assignmentUrl($clientA).'/999999', $admin)->assertNotFound();
        $clientB->delete();
        $this->apiGet($this->assignmentUrl($clientB), $admin)->assertNotFound();

        $activeClient = $this->client('Cliente activo');
        $agent->agente->restore();
        $activeAssignment = ClienteAgente::create(['cliente_id' => $activeClient->id, 'agente_id' => $agent->agente->id, 'es_principal' => true]);
        $agent->agente->delete();
        $this->apiGet($this->assignmentUrl($activeClient), $admin)->assertOk()->assertJsonCount(0, 'data');
        $this->apiPatch($this->principalUrl($activeClient, $activeAssignment), $admin, [])->assertNotFound();
    }

    public function test_assignment_changes_drive_client_visibility_without_principal_affecting_scope(): void
    {
        $admin = $this->user('visibility-client-agent-admin@example.test', 'Administrador');
        $agent = $this->agentUser('visibility-client-agent@example.test');
        $other = $this->agentUser('visibility-client-agent-other@example.test');
        $cliente = $this->client('Visible después');

        $this->apiGet('/api/v1/clientes', $agent)->assertOk()->assertJsonMissing(['id' => $cliente->id]);
        $ownResponse = $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $agent->agente->id])->assertCreated();
        $otherResponse = $this->apiPost($this->assignmentUrl($cliente), $admin, ['agente_id' => $other->agente->id])->assertCreated();
        $this->apiGet('/api/v1/clientes', $agent)->assertOk()->assertJsonFragment(['id' => $cliente->id]);

        $otherAssignment = ClienteAgente::query()->findOrFail($otherResponse->json('data.id'));
        $this->apiPatch($this->principalUrl($cliente, $otherAssignment), $admin, [])->assertOk();
        $this->apiGet('/api/v1/clientes', $agent)->assertOk()->assertJsonFragment(['id' => $cliente->id]);

        $ownAssignment = ClienteAgente::query()->findOrFail($ownResponse->json('data.id'));
        $this->apiDelete($this->assignmentUrl($cliente).'/'.$ownAssignment->id, $admin)->assertNoContent();
        $this->apiGet('/api/v1/clientes', $agent)->assertOk()->assertJsonMissing(['id' => $cliente->id]);
    }

    public function test_create_cliente_compatibility_keeps_agent_assignment_as_principal(): void
    {
        $agent = $this->agentUser('compatibility-client-agent@example.test');

        $this->apiPost('/api/v1/clientes', $agent, [
            'nombres' => 'Prospecto compatible',
            'apellido_paterno' => 'Agente',
        ])->assertCreated();

        $cliente = Cliente::query()->where('nombres', 'Prospecto compatible')->firstOrFail();
        $assignment = ClienteAgente::query()->where('cliente_id', $cliente->id)->firstOrFail();
        self::assertSame($agent->agente->id, $assignment->agente_id);
        self::assertTrue((bool) $assignment->es_principal);
        self::assertSame(1, ClienteAgente::query()->where('cliente_id', $cliente->id)->count());
    }

    private function user(string $email, ?string $role = null): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Cliente Agente',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    private function agentUser(string $email, string $state = 'activo'): User
    {
        $user = $this->user($email, 'Agente Inmobiliario');
        $sequence = ++self::$sequence;

        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => 'AG-CLI-'.$sequence,
            'estado_laboral' => $state,
        ]);

        return $user->fresh('agente');
    }

    private function client(string $name, ?User $user = null): Cliente
    {
        $sequence = ++self::$sequence;

        return Cliente::create([
            'user_id' => $user?->id,
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => 'cliente-'.$sequence.'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function assignmentUrl(Cliente $cliente): string
    {
        return '/api/v1/clientes/'.$cliente->id.'/agentes';
    }

    private function principalUrl(Cliente $cliente, ClienteAgente $assignment): string
    {
        return $this->assignmentUrl($cliente).'/'.$assignment->id.'/principal';
    }

    private function apiGet(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }

    /** @param array<string, mixed> $payload */
    private function apiPost(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson($uri, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function apiPatch(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson($uri, $payload);
    }

    private function apiDelete(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson($uri);
    }
}
