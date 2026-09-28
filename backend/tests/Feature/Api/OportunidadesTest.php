<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\Inmueble;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\SolicitudInformacion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OportunidadesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_private_opportunities_endpoint_requires_authentication(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/oportunidades')
            ->assertUnauthorized();
    }

    public function test_administrator_can_manage_opportunity_stage_state_history_and_delete(): void
    {
        $admin = $this->user('admin-opportunities@example.test', 'Administrador');
        $client = $this->client('Cliente oportunidad');
        $property = $this->property('OPP-ADMIN');
        $agent = $this->agentUser('principal-opportunity@example.test');

        $created = $this->apiPost('/api/v1/oportunidades', $admin, [
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'agente_principal_id' => $agent->agente->id,
            'titulo' => 'Venta residencial',
            'notas' => 'Seguimiento inicial',
        ])->assertCreated()
            ->assertJsonPath('data.etapa', 'contacto_inicial')
            ->assertJsonPath('data.estado', 'activa')
            ->assertJsonPath('data.agente_principal_id', $agent->agente->id)
            ->assertJsonMissingPath('data.historial');

        $opportunity = Oportunidad::query()->findOrFail($created->json('data.id'));
        self::assertNotNull($opportunity->fecha_apertura);
        self::assertDatabaseHas('oportunidad_historial', [
            'oportunidad_id' => $opportunity->id,
            'tipo_evento' => 'creacion',
            'cambiado_por_user_id' => $admin->id,
            'etapa_nueva' => 'contacto_inicial',
            'estado_nuevo' => 'activa',
        ]);

        $this->apiGet('/api/v1/oportunidades', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id, $admin)
            ->assertOk()
            ->assertJsonPath('data.cliente.id', $client->id)
            ->assertJsonPath('data.inmueble.id', $property->id)
            ->assertJsonMissingPath('data.agente_principal.user.password');

        $this->apiPatch('/api/v1/oportunidades/'.$opportunity->id, $admin, [
            'titulo' => 'Venta residencial actualizada',
            'fecha_cierre' => '2026-10-10 12:00:00',
        ])->assertOk()
            ->assertJsonPath('data.titulo', 'Venta residencial actualizada');

        $this->apiPatch('/api/v1/oportunidades/'.$opportunity->id.'/etapa', $admin, [
            'etapa' => 'negociacion',
            'comentario' => 'Cliente confirmó interés',
        ])->assertOk()
            ->assertJsonPath('data.etapa', 'negociacion');
        $this->apiPatch('/api/v1/oportunidades/'.$opportunity->id.'/etapa', $admin, [
            'etapa' => 'negociacion',
        ])->assertOk();
        $this->apiPatch('/api/v1/oportunidades/'.$opportunity->id.'/estado', $admin, [
            'estado' => 'ganada',
            'comentario' => 'Operación aprobada',
        ])->assertOk()
            ->assertJsonPath('data.estado', 'ganada');

        self::assertDatabaseCount('oportunidad_historial', 3);

        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id.'/historial', $admin)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.tipo_evento', 'cambio_estado')
            ->assertJsonMissingPath('data.0.updated_at');

        $this->apiDelete('/api/v1/oportunidades/'.$opportunity->id, $admin)
            ->assertNoContent();
        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id, $admin)->assertNotFound();
        self::assertSoftDeleted('oportunidades', ['id' => $opportunity->id]);
        self::assertDatabaseCount('oportunidad_historial', 3);
        self::assertDatabaseHas('clientes', ['id' => $client->id]);
        self::assertDatabaseHas('inmuebles', ['id' => $property->id]);
    }

    public function test_assistant_can_manage_but_cannot_delete_and_director_is_read_only(): void
    {
        $assistant = $this->user('assistant-opportunities@example.test', 'Asistente');
        $director = $this->user('director-opportunities@example.test', 'Director General');
        $client = $this->client('Cliente roles oportunidad');
        $opportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'titulo' => 'Oportunidad existente',
        ]);

        $this->apiGet('/api/v1/oportunidades', $assistant)->assertOk();
        $this->apiPatch('/api/v1/oportunidades/'.$opportunity->id, $assistant, [
            'notas' => 'Nota de asistente',
        ])->assertOk();
        $this->apiPatch('/api/v1/oportunidades/'.$opportunity->id.'/etapa', $assistant, [
            'etapa' => 'cita',
        ])->assertOk();
        $this->apiDelete('/api/v1/oportunidades/'.$opportunity->id, $assistant)->assertForbidden();

        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id, $director)->assertOk();
        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id.'/historial', $director)->assertOk();
        $this->apiPost('/api/v1/oportunidades', $director, [
            'cliente_id' => $client->id,
            'titulo' => 'No permitida',
        ])->assertForbidden();
        $this->apiPatch('/api/v1/oportunidades/'.$opportunity->id, $director, [
            'notas' => 'No permitida',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/oportunidades/'.$opportunity->id, $director)->assertForbidden();
    }

    public function test_client_has_no_opportunity_access(): void
    {
        $clientUser = $this->user('client-opportunities@example.test', 'Cliente');
        $client = $this->client('Cliente portal', $clientUser);
        $opportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'titulo' => 'Privada',
        ]);

        $this->apiGet('/api/v1/oportunidades', $clientUser)->assertForbidden();
        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id, $clientUser)->assertForbidden();
    }

    public function test_agent_create_derives_principal_and_requires_client_and_property_scope(): void
    {
        $agent = $this->agentUser('agent-opportunities@example.test');
        $assignedClient = $this->client('Cliente asignado');
        $assignedProperty = $this->property('OPP-AGENT');
        $foreignClient = $this->client('Cliente ajeno');
        $foreignProperty = $this->property('OPP-FOREIGN');

        ClienteAgente::create(['cliente_id' => $assignedClient->id, 'agente_id' => $agent->agente->id]);
        AgenteInmueble::create(['inmueble_id' => $assignedProperty->id, 'agente_id' => $agent->agente->id]);

        $created = $this->apiPost('/api/v1/oportunidades', $agent, [
            'cliente_id' => $assignedClient->id,
            'inmueble_id' => $assignedProperty->id,
            'titulo' => 'Oportunidad del agente',
        ])->assertCreated()
            ->assertJsonPath('data.agente_principal_id', $agent->agente->id);

        $this->apiPost('/api/v1/oportunidades', $agent, [
            'cliente_id' => $assignedClient->id,
            'inmueble_id' => $assignedProperty->id,
            'agente_principal_id' => $agent->agente->id,
            'titulo' => 'Campo prohibido',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/oportunidades', $agent, [
            'cliente_id' => $assignedClient->id,
            'inmueble_id' => $foreignProperty->id,
            'titulo' => 'Propiedad ajena',
        ])->assertUnprocessable();
        $this->apiPost('/api/v1/oportunidades', $agent, [
            'cliente_id' => $foreignClient->id,
            'titulo' => 'Cliente ajeno',
        ])->assertUnprocessable();

        $otherAgent = $this->agentUser('other-opportunity-agent@example.test');
        $admin = $this->user('admin-opportunity-scope@example.test', 'Administrador');
        $indirect = $this->apiPost('/api/v1/oportunidades', $admin, [
            'cliente_id' => $assignedClient->id,
            'inmueble_id' => $assignedProperty->id,
            'agente_principal_id' => $otherAgent->agente->id,
            'titulo' => 'Acceso indirecto',
        ])->assertCreated();

        $this->apiGet('/api/v1/oportunidades', $agent)
            ->assertOk()
            ->assertJsonFragment(['id' => $created->json('data.id')])
            ->assertJsonFragment(['id' => $indirect->json('data.id')]);
        $this->apiPatch('/api/v1/oportunidades/'.$indirect->json('data.id'), $agent, [
            'notas' => 'No puede editar indirecta',
        ])->assertForbidden();
        $this->apiPatch('/api/v1/oportunidades/'.$indirect->json('data.id').'/etapa', $agent, [
            'etapa' => 'cita',
        ])->assertForbidden();
    }

    public function test_create_validates_soft_deleted_relations_source_coherence_and_unique(): void
    {
        $admin = $this->user('admin-opportunity-validation@example.test', 'Administrador');
        $client = $this->client('Cliente fuente');
        $otherClient = $this->client('Cliente diferente');
        $property = $this->property('OPP-SOURCE');
        $otherProperty = $this->property('OPP-SOURCE-OTHER');
        $request = SolicitudInformacion::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'nombre' => 'Solicitud fuente',
            'email' => 'source@example.test',
        ]);

        $created = $this->apiPost('/api/v1/oportunidades', $admin, [
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'solicitud_informacion_id' => $request->id,
            'titulo' => 'Desde solicitud',
        ])->assertCreated();

        $this->apiPost('/api/v1/oportunidades', $admin, [
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'solicitud_informacion_id' => $request->id,
            'titulo' => 'Duplicada',
        ])->assertUnprocessable();

        Oportunidad::query()->findOrFail($created->json('data.id'))->delete();
        $this->apiPost('/api/v1/oportunidades', $admin, [
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'solicitud_informacion_id' => $request->id,
            'titulo' => 'Reutiliza soft deleted',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/oportunidades', $admin, [
            'cliente_id' => $otherClient->id,
            'inmueble_id' => $otherProperty->id,
            'solicitud_informacion_id' => $request->id,
            'titulo' => 'Cliente incoherente',
        ])->assertUnprocessable();

        $deletedClient = $this->client('Cliente eliminado');
        $deletedClient->delete();
        $deletedProperty = $this->property('OPP-DELETED');
        $deletedProperty->delete();
        $this->apiPost('/api/v1/oportunidades', $admin, [
            'cliente_id' => $deletedClient->id,
            'inmueble_id' => $deletedProperty->id,
            'titulo' => 'FK eliminada',
        ])->assertUnprocessable();

        foreach (['etapa', 'estado', 'fecha_apertura', 'fecha_cierre', 'motivo_perdida', 'historial', 'cambiado_por_user_id'] as $field) {
            $this->apiPost('/api/v1/oportunidades', $admin, [
                'cliente_id' => $client->id,
                'titulo' => 'Campo prohibido',
                $field => 'valor',
            ])->assertUnprocessable();
        }
    }

    public function test_index_filters_and_q_do_not_escape_agent_visibility(): void
    {
        $agent = $this->agentUser('agent-opportunity-filter@example.test');
        $assignedClient = $this->client('Cliente filtro visible');
        $foreignClient = $this->client('Cliente filtro secreto');
        $property = $this->property('OPP-FILTER');
        ClienteAgente::create(['cliente_id' => $assignedClient->id, 'agente_id' => $agent->agente->id]);
        AgenteInmueble::create(['inmueble_id' => $property->id, 'agente_id' => $agent->agente->id]);

        $visible = Oportunidad::create([
            'cliente_id' => $assignedClient->id,
            'inmueble_id' => $property->id,
            'titulo' => 'Secreto compartido',
            'etapa' => 'cita',
        ]);
        $hidden = Oportunidad::create([
            'cliente_id' => $foreignClient->id,
            'titulo' => 'Secreto compartido',
        ]);

        $this->apiGet('/api/v1/oportunidades?q=Secreto%20compartido&etapa=cita', $agent)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonMissing(['id' => $hidden->id]);
        $this->apiGet('/api/v1/oportunidades?sort=not_allowed', $agent)->assertUnprocessable();
        $this->apiGet('/api/v1/oportunidades?per_page=101', $agent)->assertUnprocessable();
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Oportunidades',
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
            'estado_laboral' => 'activo',
        ]);

        return $user->fresh(['agente']);
    }

    private function client(string $name, ?User $user = null): Cliente
    {
        return Cliente::create([
            'user_id' => $user?->id,
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function property(string $code): Inmueble
    {
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario '.$code,
            'rfc' => strtoupper(substr(md5('owner-'.$code), 0, 13)),
            'telefono' => '5555555555',
            'direccion' => 'Dirección de prueba',
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
            'calle' => 'Calle de prueba',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '01000',
        ]);
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
