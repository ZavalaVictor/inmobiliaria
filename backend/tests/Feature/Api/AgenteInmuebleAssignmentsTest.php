<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AgenteInmuebleAssignmentsTest extends TestCase
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
        $property = $this->property('AUTH-001');

        $this->getJson('/api/v1/inmuebles/'.$property->id.'/agentes')
            ->assertUnauthorized();
    }

    public function test_administrator_can_manage_assignments_and_preserve_principal_invariant(): void
    {
        $admin = $this->user('admin-assignments@example.test', 'Administrador');
        $property = $this->property('ADMIN-001');
        $firstAgent = $this->agentUser('first-assignment@example.test');
        $secondAgent = $this->agentUser('second-assignment@example.test');

        $first = $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $firstAgent->agente->id,
        ])->assertCreated()
            ->assertJsonPath('data.es_principal', true)
            ->assertJsonPath('data.agente.id', $firstAgent->agente->id)
            ->assertJsonMissingPath('data.agente.user.password');

        $firstAssignment = AgenteInmueble::query()->findOrFail($first->json('data.id'));
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'agente_inmueble_asignado',
            'entidad' => 'agente_inmueble',
            'entidad_id' => $firstAssignment->id,
            'user_id' => $admin->id,
        ]);

        $second = $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $secondAgent->agente->id,
            'es_principal' => false,
        ])->assertCreated()
            ->assertJsonPath('data.es_principal', false);

        $secondAssignment = AgenteInmueble::query()->findOrFail($second->json('data.id'));

        $this->apiGet($this->assignmentUrl($property), $admin)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->apiPatch($this->principalUrl($property, $secondAssignment), $admin, [])
            ->assertOk()
            ->assertJsonPath('data.es_principal', true);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'agente_inmueble_principal_cambiado',
            'entidad_id' => $secondAssignment->id,
            'user_id' => $admin->id,
        ]);

        self::assertFalse((bool) $firstAssignment->fresh()->es_principal);
        self::assertTrue((bool) $secondAssignment->fresh()->es_principal);

        $this->apiDelete($this->assignmentUrl($property).'/'.$secondAssignment->id, $admin)
            ->assertNoContent();
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'agente_inmueble_desasignado',
            'entidad_id' => $secondAssignment->id,
            'user_id' => $admin->id,
        ]);

        self::assertTrue((bool) $firstAssignment->fresh()->es_principal);

        $this->apiDelete($this->assignmentUrl($property).'/'.$firstAssignment->id, $admin)
            ->assertNoContent();

        self::assertSame(0, $property->asignacionesAgentes()->count());
        self::assertDatabaseHas('agentes', ['id' => $firstAgent->agente->id]);
        self::assertDatabaseHas('inmuebles', ['id' => $property->id]);
    }

    public function test_assistant_can_assign_and_change_principal_but_cannot_delete(): void
    {
        $assistant = $this->user('assistant-assignments@example.test', 'Asistente');
        $property = $this->property('ASSISTANT-001');
        $firstAgent = $this->agentUser('assistant-first@example.test');
        $secondAgent = $this->agentUser('assistant-second@example.test');

        $this->apiPost($this->assignmentUrl($property), $assistant, [
            'agente_id' => $firstAgent->agente->id,
        ])->assertCreated();
        $second = $this->apiPost($this->assignmentUrl($property), $assistant, [
            'agente_id' => $secondAgent->agente->id,
        ])->assertCreated();

        $assignment = AgenteInmueble::query()->findOrFail($second->json('data.id'));

        $this->apiPatch($this->principalUrl($property, $assignment), $assistant, [])
            ->assertOk()
            ->assertJsonPath('data.es_principal', true);
        $this->apiDelete($this->assignmentUrl($property).'/'.$assignment->id, $assistant)
            ->assertForbidden();
    }

    public function test_director_can_only_list_assignments(): void
    {
        $director = $this->user('director-assignments@example.test', 'Director General');
        $property = $this->property('DIRECTOR-001');
        $agent = $this->agentUser('director-agent@example.test');
        $otherAgent = $this->agentUser('director-other-agent@example.test');
        $assignment = AgenteInmueble::create([
            'agente_id' => $agent->agente->id,
            'inmueble_id' => $property->id,
            'es_principal' => true,
        ]);

        $this->apiGet($this->assignmentUrl($property), $director)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiPost($this->assignmentUrl($property), $director, [
            'agente_id' => $otherAgent->agente->id,
        ])->assertForbidden();
        $this->apiPatch($this->principalUrl($property, $assignment), $director, [])
            ->assertForbidden();
        $this->apiDelete($this->assignmentUrl($property).'/'.$assignment->id, $director)
            ->assertForbidden();
    }

    public function test_agent_only_sees_own_assignment_and_cannot_mutate_assignments(): void
    {
        $agentUser = $this->agentUser('scoped-assignment-agent@example.test');
        $otherAgent = $this->agentUser('scoped-assignment-other@example.test');
        $thirdAgent = $this->agentUser('scoped-assignment-third@example.test');
        $admin = $this->user('scoped-assignment-admin@example.test', 'Administrador');
        $property = $this->property('AGENT-001');

        $own = $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $agentUser->agente->id,
        ])->assertCreated();
        $other = $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $otherAgent->agente->id,
        ])->assertCreated();

        $this->apiGet($this->assignmentUrl($property), $agentUser)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.agente_id', $agentUser->agente->id);
        $this->apiPost($this->assignmentUrl($property), $agentUser, [
            'agente_id' => $thirdAgent->agente->id,
        ])->assertForbidden();

        $ownAssignment = AgenteInmueble::query()->findOrFail($own->json('data.id'));
        $otherAssignment = AgenteInmueble::query()->findOrFail($other->json('data.id'));
        $this->apiPatch($this->principalUrl($property, $ownAssignment), $agentUser, [])
            ->assertForbidden();
        $this->apiDelete($this->assignmentUrl($property).'/'.$ownAssignment->id, $agentUser)
            ->assertForbidden();
        $this->apiPatch($this->principalUrl($property, $otherAssignment), $agentUser, [])
            ->assertForbidden();
    }

    public function test_client_cannot_access_assignments(): void
    {
        $client = $this->user('client-assignments@example.test', 'Cliente');
        $property = $this->property('CLIENT-001');

        $this->apiGet($this->assignmentUrl($property), $client)->assertForbidden();
    }

    public function test_first_assignment_is_principal_even_when_omitted_or_false(): void
    {
        foreach ([[], ['es_principal' => false], ['es_principal' => true]] as $index => $options) {
            $admin = $this->user('first-principal-'.$index.'@example.test', 'Administrador');
            $property = $this->property('FIRST-'.$index);
            $agent = $this->agentUser('first-principal-agent-'.$index.'@example.test');

            $this->apiPost($this->assignmentUrl($property), $admin, array_merge([
                'agente_id' => $agent->agente->id,
            ], $options))
                ->assertCreated()
                ->assertJsonPath('data.es_principal', true);
        }
    }

    public function test_second_assignment_defaults_to_secondary_and_true_replaces_principal(): void
    {
        $admin = $this->user('second-principal@example.test', 'Administrador');
        $property = $this->property('SECOND-001');
        $firstAgent = $this->agentUser('second-first@example.test');
        $secondAgent = $this->agentUser('second-second@example.test');

        $first = $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $firstAgent->agente->id,
        ])->assertCreated();
        $second = $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $secondAgent->agente->id,
        ])->assertCreated()
            ->assertJsonPath('data.es_principal', false);

        $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $firstAgent->agente->id,
        ])->assertUnprocessable();

        $secondAssignment = AgenteInmueble::query()->findOrFail($second->json('data.id'));
        $this->apiPatch($this->principalUrl($property, $secondAssignment), $admin, [])
            ->assertOk();

        self::assertFalse((bool) AgenteInmueble::query()->findOrFail($first->json('data.id'))->es_principal);
        self::assertSame(1, AgenteInmueble::query()
            ->where('inmueble_id', $property->id)
            ->where('es_principal', true)
            ->count());
    }

    public function test_set_principal_is_idempotent_and_does_not_delete_assignments(): void
    {
        $admin = $this->user('idempotent-principal@example.test', 'Administrador');
        $property = $this->property('IDEMPOTENT-001');
        $firstAgent = $this->agentUser('idempotent-first@example.test');
        $secondAgent = $this->agentUser('idempotent-second@example.test');
        $first = AgenteInmueble::create([
            'agente_id' => $firstAgent->agente->id,
            'inmueble_id' => $property->id,
            'es_principal' => true,
        ]);
        $second = AgenteInmueble::create([
            'agente_id' => $secondAgent->agente->id,
            'inmueble_id' => $property->id,
            'es_principal' => false,
        ]);
        $beforePrincipalEvents = Bitacora::query()->where('accion', 'agente_inmueble_principal_cambiado')->count();

        $this->apiPatch($this->principalUrl($property, $first), $admin, [])
            ->assertOk()
            ->assertJsonPath('data.es_principal', true);

        self::assertDatabaseCount('agente_inmueble', 2);
        self::assertTrue((bool) $first->fresh()->es_principal);
        self::assertFalse((bool) $second->fresh()->es_principal);
        self::assertSame($beforePrincipalEvents, Bitacora::query()->where('accion', 'agente_inmueble_principal_cambiado')->count());
    }

    public function test_delete_principal_promotes_by_date_then_id_and_last_delete_leaves_none(): void
    {
        $admin = $this->user('promote-principal@example.test', 'Administrador');
        $property = $this->property('PROMOTE-001');
        $firstAgent = $this->agentUser('promote-first@example.test');
        $secondAgent = $this->agentUser('promote-second@example.test');
        $thirdAgent = $this->agentUser('promote-third@example.test');
        $first = AgenteInmueble::create([
            'agente_id' => $firstAgent->agente->id,
            'inmueble_id' => $property->id,
            'es_principal' => true,
            'fecha_asignacion' => '2026-01-03 10:00:00',
        ]);
        $second = AgenteInmueble::create([
            'agente_id' => $secondAgent->agente->id,
            'inmueble_id' => $property->id,
            'es_principal' => false,
            'fecha_asignacion' => '2026-01-01 10:00:00',
        ]);
        $third = AgenteInmueble::create([
            'agente_id' => $thirdAgent->agente->id,
            'inmueble_id' => $property->id,
            'es_principal' => false,
            'fecha_asignacion' => '2026-01-01 10:00:00',
        ]);

        $this->apiDelete($this->assignmentUrl($property).'/'.$first->id, $admin)
            ->assertNoContent();

        self::assertTrue((bool) $second->fresh()->es_principal);
        self::assertFalse((bool) $third->fresh()->es_principal);

        $this->apiDelete($this->assignmentUrl($property).'/'.$second->id, $admin)
            ->assertNoContent();
        self::assertTrue((bool) $third->fresh()->es_principal);

        $this->apiDelete($this->assignmentUrl($property).'/'.$third->id, $admin)
            ->assertNoContent();
        self::assertSame(0, $property->asignacionesAgentes()->count());
    }

    public function test_create_validates_agent_eligibility_duplicate_and_prohibited_fields(): void
    {
        $admin = $this->user('validation-assignments@example.test', 'Administrador');
        $property = $this->property('VALIDATION-001');
        $agent = $this->agentUser('validation-agent@example.test');
        $inactiveAgent = $this->agentUser('validation-inactive@example.test', 'inactivo');
        $softDeletedAgent = $this->agentUser('validation-deleted@example.test');
        $softDeletedAgent->agente->delete();

        $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => 999999,
        ])->assertUnprocessable();
        $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $softDeletedAgent->agente->id,
        ])->assertUnprocessable();

        $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $inactiveAgent->agente->id,
        ])->assertCreated();

        $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $inactiveAgent->agente->id,
        ])->assertUnprocessable();

        foreach (['inmueble_id', 'fecha_asignacion', 'id', 'created_at', 'updated_at', 'roles', 'permisos', 'agente', 'inmueble'] as $field) {
            $this->apiPost($this->assignmentUrl($property), $admin, [
                'agente_id' => $agent->agente->id,
                $field => $field === 'id' ? 99 : ['forbidden'],
            ])->assertUnprocessable();
        }
    }

    public function test_set_principal_rejects_payload_fields(): void
    {
        $admin = $this->user('principal-payload@example.test', 'Administrador');
        $property = $this->property('PRINCIPAL-PAYLOAD-001');
        $agent = $this->agentUser('principal-payload-agent@example.test');
        $assignment = AgenteInmueble::create([
            'agente_id' => $agent->agente->id,
            'inmueble_id' => $property->id,
            'es_principal' => true,
        ]);

        $this->apiPatch($this->principalUrl($property, $assignment), $admin, [
            'agente_id' => 999,
        ])->assertUnprocessable();
        self::assertTrue((bool) $assignment->fresh()->es_principal);
    }

    public function test_scoped_binding_rejects_other_property_missing_and_soft_deleted_agents(): void
    {
        $admin = $this->user('scoped-binding-assignments@example.test', 'Administrador');
        $propertyA = $this->property('SCOPE-A');
        $propertyB = $this->property('SCOPE-B');
        $agent = $this->agentUser('scoped-binding-agent@example.test');
        $assignment = AgenteInmueble::create([
            'agente_id' => $agent->agente->id,
            'inmueble_id' => $propertyB->id,
            'es_principal' => true,
        ]);

        $this->apiPatch($this->principalUrl($propertyA, $assignment), $admin, [])
            ->assertNotFound();
        $this->apiDelete($this->assignmentUrl($propertyA).'/999999', $admin)
            ->assertNotFound();

        $agent->agente->delete();
        $this->apiGet($this->assignmentUrl($propertyB), $admin)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiPatch($this->principalUrl($propertyB, $assignment), $admin, [])
            ->assertNotFound();
    }

    public function test_assignment_changes_drive_property_visibility_without_changing_on_principal_switch(): void
    {
        $admin = $this->user('visibility-assignments-admin@example.test', 'Administrador');
        $agentUser = $this->agentUser('visibility-assignments-agent@example.test');
        $otherAgent = $this->agentUser('visibility-assignments-other@example.test');
        $property = $this->property('VISIBILITY-001');

        $this->apiGet('/api/v1/inmuebles', $agentUser)
            ->assertOk()
            ->assertJsonMissing(['id' => $property->id]);

        $own = $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $agentUser->agente->id,
        ])->assertCreated();
        $other = $this->apiPost($this->assignmentUrl($property), $admin, [
            'agente_id' => $otherAgent->agente->id,
        ])->assertCreated();

        $this->apiGet('/api/v1/inmuebles', $agentUser)
            ->assertOk()
            ->assertJsonFragment(['id' => $property->id]);

        $otherAssignment = AgenteInmueble::query()->findOrFail($other->json('data.id'));
        $this->apiPatch($this->principalUrl($property, $otherAssignment), $admin, [])
            ->assertOk();
        $this->apiGet('/api/v1/inmuebles', $agentUser)
            ->assertOk()
            ->assertJsonFragment(['id' => $property->id]);

        $ownAssignment = AgenteInmueble::query()->findOrFail($own->json('data.id'));
        $this->apiDelete($this->assignmentUrl($property).'/'.$ownAssignment->id, $admin)
            ->assertNoContent();
        $this->apiGet('/api/v1/inmuebles', $agentUser)
            ->assertOk()
            ->assertJsonMissing(['id' => $property->id]);
    }

    private function user(string $email, ?string $role = null): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Asignaciones',
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
            'numero_empleado' => 'AG-ASG-'.$sequence,
            'estado_laboral' => $state,
        ]);

        return $user->fresh('agente');
    }

    private function property(string $code): Inmueble
    {
        $sequence = ++self::$sequence;
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario '.$sequence,
            'rfc' => 'RFC'.str_pad((string) $sequence, 10, '0', STR_PAD_LEFT),
            'telefono' => '5555555555',
            'direccion' => 'Dirección '.$sequence,
        ]);
        $category = Categoria::create(['nombre' => 'Categoría '.$sequence]);

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
        ]);
    }

    private function assignmentUrl(Inmueble $property): string
    {
        return '/api/v1/inmuebles/'.$property->id.'/agentes';
    }

    private function principalUrl(Inmueble $property, AgenteInmueble $assignment): string
    {
        return $this->assignmentUrl($property).'/'.$assignment->id.'/principal';
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
