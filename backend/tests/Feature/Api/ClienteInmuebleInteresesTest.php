<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\ClienteInmuebleInteres;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ClienteInmuebleInteresesTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_interests(): void
    {
        $cliente = $this->client('No autenticado');

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson($this->interestUrl($cliente))
            ->assertUnauthorized();
    }

    public function test_administrator_can_manage_interests_and_resource_is_safe(): void
    {
        $admin = $this->user('admin-interests@example.test', 'Administrador');
        $cliente = $this->client('Administrado');
        $property = $this->property('INT-ADMIN', true);

        $created = $this->apiPost($this->interestUrl($cliente), $admin, [
            'inmueble_id' => $property->id,
            'nivel_interes' => 'alto',
            'notas' => 'Jardín',
        ])->assertCreated()
            ->assertJsonPath('data.inmueble_id', $property->id)
            ->assertJsonPath('data.nivel_interes', 'alto')
            ->assertJsonMissingPath('data.deleted_at')
            ->assertJsonMissingPath('data.inmueble.propietario')
            ->assertJsonMissingPath('data.inmueble.password');

        $interest = ClienteInmuebleInteres::query()->findOrFail($created->json('data.id'));
        $this->apiGet($this->interestUrl($cliente).'/'.$interest->id, $admin)
            ->assertOk()
            ->assertJsonPath('data.estado', 'activo');
        $this->apiPatch($this->interestUrl($cliente).'/'.$interest->id, $admin, [
            'estado' => 'convertido',
            'nivel_interes' => null,
            'notas' => null,
        ])->assertOk()
            ->assertJsonPath('data.estado', 'convertido')
            ->assertJsonPath('data.nivel_interes', null);
        $this->apiDelete($this->interestUrl($cliente).'/'.$interest->id, $admin)
            ->assertNoContent();

        self::assertNotNull($interest->fresh()->deleted_at);
        self::assertDatabaseHas('clientes', ['id' => $cliente->id]);
        self::assertDatabaseHas('inmuebles', ['id' => $property->id]);
    }

    public function test_assistant_can_read_create_update_but_cannot_delete(): void
    {
        $assistant = $this->user('assistant-interests@example.test', 'Asistente');
        $cliente = $this->client('Asistente');
        $property = $this->property('INT-ASSISTANT', false);

        $created = $this->apiPost($this->interestUrl($cliente), $assistant, [
            'inmueble_id' => $property->id,
        ])->assertCreated();
        $interest = ClienteInmuebleInteres::query()->findOrFail($created->json('data.id'));

        $this->apiGet($this->interestUrl($cliente), $assistant)->assertOk()->assertJsonCount(1, 'data');
        $this->apiPatch($this->interestUrl($cliente).'/'.$interest->id, $assistant, [
            'estado' => 'descartado',
        ])->assertOk();
        $this->apiDelete($this->interestUrl($cliente).'/'.$interest->id, $assistant)->assertForbidden();
    }

    public function test_director_can_only_read_interests(): void
    {
        $director = $this->user('director-interests@example.test', 'Director General');
        $cliente = $this->client('Director');
        $property = $this->property('INT-DIRECTOR', true);
        $interest = ClienteInmuebleInteres::create([
            'cliente_id' => $cliente->id,
            'inmueble_id' => $property->id,
        ]);

        $this->apiGet($this->interestUrl($cliente), $director)->assertOk()->assertJsonCount(1, 'data');
        $this->apiGet($this->interestUrl($cliente).'/'.$interest->id, $director)->assertOk();
        $this->apiPost($this->interestUrl($cliente), $director, ['inmueble_id' => $property->id])->assertForbidden();
        $this->apiPatch($this->interestUrl($cliente).'/'.$interest->id, $director, ['estado' => 'descartado'])->assertForbidden();
        $this->apiDelete($this->interestUrl($cliente).'/'.$interest->id, $director)->assertForbidden();
    }

    public function test_client_can_manage_own_interests_only_and_requires_published_property_on_create(): void
    {
        $clientUser = $this->user('portal-interest@example.test', 'Cliente');
        $cliente = $this->client('Portal', $clientUser);
        $otherClient = $this->client('Otro');
        $published = $this->property('INT-CLIENT-PUBLISHED', true);
        $unpublished = $this->property('INT-CLIENT-PRIVATE', false);

        $created = $this->apiPost($this->interestUrl($cliente), $clientUser, [
            'inmueble_id' => $published->id,
        ])->assertCreated();
        $interest = ClienteInmuebleInteres::query()->findOrFail($created->json('data.id'));

        $this->apiGet($this->interestUrl($cliente), $clientUser)->assertOk()->assertJsonCount(1, 'data');
        $this->apiPatch($this->interestUrl($cliente).'/'.$interest->id, $clientUser, [
            'estado' => 'descartado',
        ])->assertOk();
        $this->apiDelete($this->interestUrl($cliente).'/'.$interest->id, $clientUser)->assertNoContent();
        $this->apiGet($this->interestUrl($otherClient), $clientUser)->assertForbidden();

        $this->apiPost($this->interestUrl($cliente), $clientUser, [
            'inmueble_id' => $unpublished->id,
        ])->assertUnprocessable();
        self::assertDatabaseMissing('cliente_inmueble_intereses', [
            'cliente_id' => $cliente->id,
            'inmueble_id' => $unpublished->id,
        ]);
    }

    public function test_agent_reads_by_either_end_but_mutates_only_with_both_assignments(): void
    {
        $admin = $this->user('admin-agent-interests@example.test', 'Administrador');
        $agent = $this->agentUser('agent-interests@example.test');
        $otherAgent = $this->agentUser('other-agent-interests@example.test');
        $clientAssigned = $this->client('Cliente asignado');
        $propertyAssigned = $this->property('INT-AGENT', true);
        $interest = ClienteInmuebleInteres::create([
            'cliente_id' => $clientAssigned->id,
            'inmueble_id' => $propertyAssigned->id,
        ]);

        ClienteAgente::create(['cliente_id' => $clientAssigned->id, 'agente_id' => $agent->agente->id]);
        $this->apiGet($this->interestUrl($clientAssigned), $agent)->assertOk()->assertJsonCount(1, 'data');
        $this->apiPatch($this->interestUrl($clientAssigned).'/'.$interest->id, $agent, ['estado' => 'descartado'])
            ->assertForbidden();
        $this->apiDelete($this->interestUrl($clientAssigned).'/'.$interest->id, $agent)->assertForbidden();

        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $propertyAssigned->id]);
        $this->apiPatch($this->interestUrl($clientAssigned).'/'.$interest->id, $agent, ['estado' => 'descartado'])
            ->assertOk();
        $this->apiDelete($this->interestUrl($clientAssigned).'/'.$interest->id, $agent)->assertNoContent();

        $propertyOnlyClient = $this->client('Cliente por inmueble');
        $propertyOnlyInterest = ClienteInmuebleInteres::create([
            'cliente_id' => $propertyOnlyClient->id,
            'inmueble_id' => $propertyAssigned->id,
        ]);
        $this->apiGet($this->interestUrl($propertyOnlyClient), $agent)->assertOk()->assertJsonCount(1, 'data');
        $this->apiPatch($this->interestUrl($propertyOnlyClient).'/'.$propertyOnlyInterest->id, $agent, ['estado' => 'convertido'])
            ->assertForbidden();

        $otherClient = $this->client('Cliente ajeno');
        $otherProperty = $this->property('INT-AGENT-OTHER', true);
        ClienteAgente::create(['cliente_id' => $otherClient->id, 'agente_id' => $otherAgent->agente->id]);
        AgenteInmueble::create(['agente_id' => $otherAgent->agente->id, 'inmueble_id' => $otherProperty->id]);
        $otherInterest = ClienteInmuebleInteres::create([
            'cliente_id' => $otherClient->id,
            'inmueble_id' => $otherProperty->id,
        ]);
        $this->apiGet($this->interestUrl($otherClient), $agent)->assertOk()->assertJsonCount(0, 'data');
        $this->apiPatch($this->interestUrl($otherClient).'/'.$otherInterest->id, $agent, ['estado' => 'descartado'])
            ->assertForbidden();
    }

    public function test_agent_create_requires_both_client_and_property_assignments(): void
    {
        $admin = $this->user('admin-agent-create-interest@example.test', 'Administrador');
        $agent = $this->agentUser('agent-create-interest@example.test');
        $cliente = $this->client('Cliente para agente');
        $property = $this->property('INT-AGENT-CREATE', true);

        ClienteAgente::create(['cliente_id' => $cliente->id, 'agente_id' => $agent->agente->id]);
        $this->apiPost($this->interestUrl($cliente), $agent, ['inmueble_id' => $property->id])
            ->assertUnprocessable();

        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);
        $this->apiPost($this->interestUrl($cliente), $agent, ['inmueble_id' => $property->id])
            ->assertCreated();

        $restrictedClient = $this->client('Cliente restauración restringida');
        $restrictedProperty = $this->property('INT-AGENT-RESTORE-RESTRICTED', true);
        $restrictedInterest = ClienteInmuebleInteres::create([
            'cliente_id' => $restrictedClient->id,
            'inmueble_id' => $restrictedProperty->id,
        ]);
        $restrictedInterest->delete();
        ClienteAgente::create(['cliente_id' => $restrictedClient->id, 'agente_id' => $agent->agente->id]);
        $this->apiPost($this->interestUrl($restrictedClient), $agent, ['inmueble_id' => $restrictedProperty->id])
            ->assertUnprocessable();
        self::assertNotNull($restrictedInterest->fresh()->deleted_at);

        $unassignedClient = $this->client('Cliente fuera de alcance');
        $this->apiPost($this->interestUrl($unassignedClient), $agent, ['inmueble_id' => $property->id])
            ->assertForbidden();

        $this->apiPost($this->interestUrl($cliente), $admin, ['inmueble_id' => $property->id])
            ->assertUnprocessable();
    }

    public function test_create_validates_enums_duplicate_soft_deleted_property_and_prohibited_fields(): void
    {
        $admin = $this->user('validation-interest@example.test', 'Administrador');
        $cliente = $this->client('Validación');
        $property = $this->property('INT-VALIDATION', true);

        $this->apiPost($this->interestUrl($cliente), $admin, [
            'inmueble_id' => $property->id,
            'nivel_interes' => 'invalid',
        ])->assertUnprocessable();
        $this->apiPost($this->interestUrl($cliente), $admin, [
            'inmueble_id' => $property->id,
            'estado' => 'invalid',
        ])->assertUnprocessable();
        $this->apiPost($this->interestUrl($cliente), $admin, ['inmueble_id' => 999999])->assertUnprocessable();

        foreach (['cliente_id', 'fecha_interes', 'deleted_at', 'created_at', 'updated_at', 'cliente', 'inmueble', 'roles', 'permisos'] as $field) {
            $payload = [
                'inmueble_id' => $property->id,
                $field => $field === 'cliente_id' ? $cliente->id : ['forbidden'],
            ];
            $this->apiPost($this->interestUrl($cliente), $admin, $payload)->assertUnprocessable();
        }

        $created = $this->apiPost($this->interestUrl($cliente), $admin, ['inmueble_id' => $property->id])->assertCreated();
        $this->apiPost($this->interestUrl($cliente), $admin, ['inmueble_id' => $property->id])->assertUnprocessable();
        $interest = ClienteInmuebleInteres::query()->findOrFail($created->json('data.id'));
        $interest->delete();

        $property->delete();
        $this->apiPost($this->interestUrl($cliente), $admin, ['inmueble_id' => $property->id])->assertUnprocessable();
    }

    public function test_soft_deleted_pair_is_reactivated_with_same_id_and_new_episode(): void
    {
        $admin = $this->user('reactivate-interest@example.test', 'Administrador');
        $cliente = $this->client('Reactivación');
        $property = $this->property('INT-REACTIVATE', true);

        $created = $this->apiPost($this->interestUrl($cliente), $admin, [
            'inmueble_id' => $property->id,
            'nivel_interes' => 'bajo',
            'estado' => 'descartado',
            'notas' => 'Primer episodio',
        ])->assertCreated();
        $interest = ClienteInmuebleInteres::query()->findOrFail($created->json('data.id'));
        $oldDate = $interest->fecha_interes;
        $this->apiDelete($this->interestUrl($cliente).'/'.$interest->id, $admin)->assertNoContent();

        $reactivated = $this->apiPost($this->interestUrl($cliente), $admin, [
            'inmueble_id' => $property->id,
            'nivel_interes' => 'alto',
        ])->assertCreated()
            ->assertJsonPath('data.id', $interest->id)
            ->assertJsonPath('data.estado', 'activo')
            ->assertJsonPath('data.nivel_interes', 'alto');

        $fresh = ClienteInmuebleInteres::query()->findOrFail($reactivated->json('data.id'));
        self::assertNull($fresh->deleted_at);
        self::assertTrue($fresh->fecha_interes->greaterThanOrEqualTo($oldDate));
        self::assertSame(1, ClienteInmuebleInteres::withTrashed()
            ->where('cliente_id', $cliente->id)
            ->where('inmueble_id', $property->id)
            ->count());
    }

    public function test_interest_state_does_not_change_visibility_but_delete_and_reactivation_do(): void
    {
        $admin = $this->user('visibility-interest-admin@example.test', 'Administrador');
        $clientUser = $this->user('visibility-interest-client@example.test', 'Cliente');
        $cliente = $this->client('Visibilidad', $clientUser);
        $otherClientUser = $this->user('visibility-interest-other@example.test', 'Cliente');
        $otherClient = $this->client('Otro cliente', $otherClientUser);
        $property = $this->property('INT-VISIBILITY', true);

        $this->apiGet('/api/v1/inmuebles', $clientUser)->assertOk()->assertJsonMissing(['id' => $property->id]);
        $created = $this->apiPost($this->interestUrl($cliente), $clientUser, ['inmueble_id' => $property->id])->assertCreated();
        $interest = ClienteInmuebleInteres::query()->findOrFail($created->json('data.id'));
        $this->apiGet('/api/v1/inmuebles', $clientUser)->assertOk()->assertJsonFragment(['id' => $property->id]);

        foreach (['descartado', 'convertido', 'activo'] as $state) {
            $this->apiPatch($this->interestUrl($cliente).'/'.$interest->id, $clientUser, ['estado' => $state])->assertOk();
            $this->apiGet('/api/v1/inmuebles', $clientUser)->assertOk()->assertJsonFragment(['id' => $property->id]);
        }

        $this->apiGet('/api/v1/inmuebles', $otherClientUser)->assertOk()->assertJsonMissing(['id' => $property->id]);
        $this->apiDelete($this->interestUrl($cliente).'/'.$interest->id, $clientUser)->assertNoContent();
        $this->apiGet('/api/v1/inmuebles', $clientUser)->assertOk()->assertJsonMissing(['id' => $property->id]);
        $this->apiPost($this->interestUrl($cliente), $clientUser, ['inmueble_id' => $property->id])->assertCreated();
        $this->apiGet('/api/v1/inmuebles', $clientUser)->assertOk()->assertJsonFragment(['id' => $property->id]);
        self::assertDatabaseMissing('cliente_inmueble_intereses', ['cliente_id' => $otherClient->id, 'inmueble_id' => $property->id]);
    }

    public function test_scoped_binding_excludes_cross_client_soft_deleted_and_soft_deleted_property_interests(): void
    {
        $admin = $this->user('scope-interest@example.test', 'Administrador');
        $clientA = $this->client('Cliente A');
        $clientB = $this->client('Cliente B');
        $property = $this->property('INT-SCOPE', true);
        $interest = ClienteInmuebleInteres::create(['cliente_id' => $clientB->id, 'inmueble_id' => $property->id]);

        $this->apiGet($this->interestUrl($clientA).'/'.$interest->id, $admin)->assertNotFound();
        $this->apiPatch($this->interestUrl($clientA).'/'.$interest->id, $admin, ['estado' => 'descartado'])->assertNotFound();
        $this->apiDelete($this->interestUrl($clientA).'/'.$interest->id, $admin)->assertNotFound();

        $interest->delete();
        $this->apiGet($this->interestUrl($clientB).'/'.$interest->id, $admin)->assertNotFound();
        $this->apiGet($this->interestUrl($clientB), $admin)->assertOk()->assertJsonCount(0, 'data');

        $softDeletedProperty = $this->property('INT-SCOPE-DELETED', true);
        $newInterest = ClienteInmuebleInteres::create(['cliente_id' => $clientB->id, 'inmueble_id' => $softDeletedProperty->id]);
        $softDeletedProperty->delete();
        $this->apiGet($this->interestUrl($clientB).'/'.$newInterest->id, $admin)->assertNotFound();
        $clientB->delete();
        $this->apiGet($this->interestUrl($clientB), $admin)->assertNotFound();
    }

    public function test_filters_apply_after_visibility_and_do_not_change_client_visibility(): void
    {
        $admin = $this->user('filter-interest-admin@example.test', 'Administrador');
        $agent = $this->agentUser('filter-interest-agent@example.test');
        $client = $this->client('Cliente filtro');
        $otherClient = $this->client('Otro filtro');
        $property = $this->property('INT-FILTER', true);
        $otherProperty = $this->property('INT-FILTER-OTHER', true);
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);
        ClienteInmuebleInteres::create(['cliente_id' => $client->id, 'inmueble_id' => $property->id, 'nivel_interes' => 'alto']);
        ClienteInmuebleInteres::create(['cliente_id' => $otherClient->id, 'inmueble_id' => $otherProperty->id, 'nivel_interes' => 'alto']);

        $this->apiGet($this->interestUrl($client).'?nivel_interes=alto&estado=activo&inmueble_id='.$property->id, $agent)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.inmueble_id', $property->id);
        $this->apiGet('/api/v1/clientes', $agent)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $client->id);

        $this->apiGet($this->interestUrl($client).'?sort=invalid', $admin)->assertUnprocessable();
        $this->apiGet($this->interestUrl($client).'?direction=invalid', $admin)->assertUnprocessable();
    }

    private function user(string $email, ?string $role = null): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Intereses',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    private function agentUser(string $email): User
    {
        $user = $this->user($email, 'Agente Inmobiliario');
        $sequence = ++self::$sequence;

        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => 'AG-INT-'.$sequence,
            'estado_laboral' => 'activo',
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
            'email' => 'interes-client-'.$sequence.'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function property(string $code, bool $published): Inmueble
    {
        $sequence = ++self::$sequence;
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario '.$sequence,
            'rfc' => 'RFI'.str_pad((string) $sequence, 10, '0', STR_PAD_LEFT),
            'telefono' => '5555555555',
            'direccion' => 'Dirección '.$sequence,
        ]);
        $category = Categoria::create(['nombre' => 'Categoría interés '.$sequence]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => $code,
            'titulo' => 'Inmueble '.$code,
            'slug' => strtolower($code),
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle '.$sequence,
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
            'publicado' => $published,
        ]);
    }

    private function interestUrl(Cliente $cliente): string
    {
        return '/api/v1/clientes/'.$cliente->id.'/intereses';
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
