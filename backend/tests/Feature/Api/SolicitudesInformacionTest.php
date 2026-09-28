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
use App\Models\SolicitudInformacion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SolicitudesInformacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_private_endpoint_requires_authentication(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/solicitudes')
            ->assertUnauthorized();
    }

    public function test_administrator_can_manage_private_requests(): void
    {
        $admin = $this->user('admin-requests@example.test', 'Administrador');
        $client = $this->client('Cliente solicitud', 'request-client@example.test');
        $property = $this->property('request-admin');

        $created = $this->apiPost('/api/v1/solicitudes', $admin, [
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'nombre' => 'Contacto administrativo',
            'email' => 'contacto@example.test',
            'telefono' => '5555555555',
            'mensaje' => 'Solicito información.',
            'medio_preferido' => 'correo',
        ])->assertCreated()
            ->assertJsonPath('data.estado', 'nueva')
            ->assertJsonPath('data.origen', 'registro_interno')
            ->assertJsonPath('data.atendida_por_user_id', null)
            ->assertJsonPath('data.fecha_atencion', null)
            ->assertJsonMissingPath('data.deleted_at')
            ->assertJsonMissingPath('data.oportunidad');

        $solicitud = SolicitudInformacion::query()->findOrFail($created->json('data.id'));

        $this->apiGet('/api/v1/solicitudes', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/solicitudes/'.$solicitud->id, $admin)
            ->assertOk()
            ->assertJsonPath('data.cliente.id', $client->id)
            ->assertJsonPath('data.inmueble.id', $property->id)
            ->assertJsonMissingPath('data.atendida_por.password')
            ->assertJsonMissingPath('data.atendida_por.roles');

        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
            'estado' => 'en_atencion',
            'fecha_atencion' => '2026-09-28 10:00:00',
        ])->assertOk()
            ->assertJsonPath('data.estado', 'en_atencion')
            ->assertJsonPath('data.fecha_atencion', '2026-09-28T10:00:00.000000Z');

        $this->apiDelete('/api/v1/solicitudes/'.$solicitud->id, $admin)
            ->assertNoContent();
        $this->apiGet('/api/v1/solicitudes/'.$solicitud->id, $admin)->assertNotFound();
        self::assertNotNull($solicitud->fresh()->deleted_at);
        self::assertDatabaseHas('clientes', ['id' => $client->id]);
        self::assertDatabaseHas('inmuebles', ['id' => $property->id]);
    }

    public function test_assistant_can_manage_requests(): void
    {
        $assistant = $this->user('assistant-requests@example.test', 'Asistente');
        $solicitud = SolicitudInformacion::create([
            'nombre' => 'Solicitud existente',
            'email' => 'existing-request@example.test',
        ]);

        $this->apiGet('/api/v1/solicitudes', $assistant)->assertOk();
        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $assistant, [
            'estado' => 'atendida',
        ])->assertOk();

        $this->apiPost('/api/v1/solicitudes', $assistant, [
            'nombre' => 'Asistente crea',
            'email' => 'assistant-create@example.test',
        ])->assertCreated();
    }

    public function test_director_is_read_only_for_requests(): void
    {
        $director = $this->user('director-requests@example.test', 'Director General');
        $solicitud = SolicitudInformacion::create([
            'nombre' => 'Solicitud existente',
            'email' => 'existing-director-request@example.test',
        ]);

        $this->apiGet('/api/v1/solicitudes/'.$solicitud->id, $director)->assertOk();
        self::assertFalse($director->can('solicitudes.crear'));
        $this->apiPost('/api/v1/solicitudes', $director, [
            'nombre' => 'No crea',
            'email' => 'director-create@example.test',
        ])->assertForbidden();
        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $director, [
            'estado' => 'descartada',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/solicitudes/'.$solicitud->id, $director)->assertForbidden();
    }

    public function test_agent_reads_by_scope_updates_only_operational_fields_and_cannot_delete(): void
    {
        $agent = $this->agentUser('agent-requests@example.test');
        $otherAgent = $this->agentUser('other-agent-requests@example.test');
        $assignedClient = $this->client('Cliente asignado', 'assigned-request@example.test');
        $assignedProperty = $this->property('request-agent');
        $attendedOnly = SolicitudInformacion::create([
            'nombre' => 'Atendida por agente',
            'email' => 'attended-only@example.test',
            'atendida_por_user_id' => $agent->id,
        ]);
        $clientRequest = SolicitudInformacion::create([
            'cliente_id' => $assignedClient->id,
            'nombre' => 'Cliente asignado',
            'email' => 'assigned-visible@example.test',
        ]);
        $propertyRequest = SolicitudInformacion::create([
            'inmueble_id' => $assignedProperty->id,
            'nombre' => 'Inmueble asignado',
            'email' => 'property-visible@example.test',
        ]);
        $foreignRequest = SolicitudInformacion::create([
            'nombre' => 'Solicitud ajena',
            'email' => 'foreign-request@example.test',
        ]);

        ClienteAgente::create(['cliente_id' => $assignedClient->id, 'agente_id' => $agent->agente->id]);
        AgenteInmueble::create(['inmueble_id' => $assignedProperty->id, 'agente_id' => $agent->agente->id]);

        $this->apiGet('/api/v1/solicitudes', $agent)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['id' => $attendedOnly->id])
            ->assertJsonFragment(['id' => $clientRequest->id])
            ->assertJsonFragment(['id' => $propertyRequest->id])
            ->assertJsonMissing(['id' => $foreignRequest->id]);

        $this->apiPost('/api/v1/solicitudes', $agent, [
            'nombre' => 'Agente no crea',
            'email' => 'agent-create-request@example.test',
        ])->assertForbidden();

        $this->apiPatch('/api/v1/solicitudes/'.$clientRequest->id, $agent, [
            'estado' => 'en_atencion',
            'fecha_atencion' => '2026-09-28 11:00:00',
        ])->assertOk();
        $this->apiPatch('/api/v1/solicitudes/'.$clientRequest->id, $agent, [
            'nombre' => 'No puede editar contacto',
        ])->assertUnprocessable();
        $this->apiPatch('/api/v1/solicitudes/'.$clientRequest->id, $agent, [
            'atendida_por_user_id' => $agent->id,
        ])->assertUnprocessable();
        $this->apiPatch('/api/v1/solicitudes/'.$foreignRequest->id, $agent, [
            'estado' => 'atendida',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/solicitudes/'.$clientRequest->id, $agent)->assertForbidden();

        self::assertSame(0, $otherAgent->solicitudesAtendidas()->count());
    }

    public function test_agent_without_profile_fails_closed(): void
    {
        $orphanAgent = $this->user('orphan-request-agent@example.test', 'Agente Inmobiliario');
        SolicitudInformacion::create([
            'nombre' => 'Solicitud global',
            'email' => 'orphan-visible@example.test',
        ]);

        $this->apiGet('/api/v1/solicitudes', $orphanAgent)->assertForbidden();
    }

    public function test_client_can_create_own_request_with_server_origin_and_property_scope(): void
    {
        $clientUser = $this->user('portal-request@example.test', 'Cliente');
        $client = $this->client('Portal Cliente', 'portal-client@example.test', $clientUser);
        $published = $this->property('request-published', ['publicado' => true]);
        $private = $this->property('request-private');
        $visiblePrivate = $this->property('request-visible-private');
        ClienteInmuebleInteres::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $visiblePrivate->id,
        ]);

        $created = $this->apiPost('/api/v1/solicitudes', $clientUser, [
            'inmueble_id' => $published->id,
            'nombre' => 'Nombre snapshot',
            'email' => 'snapshot@example.test',
            'telefono' => '5550001111',
        ])->assertCreated()
            ->assertJsonPath('data.cliente_id', $client->id)
            ->assertJsonPath('data.origen', 'portal_cliente')
            ->assertJsonPath('data.estado', 'nueva')
            ->assertJsonPath('data.atendida_por_user_id', null);

        self::assertDatabaseCount('users', 1);
        self::assertSame(1, SolicitudInformacion::query()->count());

        $this->apiPost('/api/v1/solicitudes', $clientUser, [
            'cliente_id' => 999999,
            'nombre' => 'Forzar cliente',
            'email' => 'forced-client@example.test',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/solicitudes', $clientUser, [
            'inmueble_id' => $private->id,
            'nombre' => 'Privado ajeno',
            'email' => 'private-foreign@example.test',
        ])->assertUnprocessable();

        $this->apiPost('/api/v1/solicitudes', $clientUser, [
            'inmueble_id' => $visiblePrivate->id,
            'nombre' => 'Privado visible',
            'email' => 'private-visible@example.test',
        ])->assertCreated();

        $foreignClient = $this->client('Cliente ajeno', 'foreign-portal-client@example.test');
        $foreignRequest = SolicitudInformacion::create([
            'cliente_id' => $foreignClient->id,
            'nombre' => 'Solicitud ajena',
            'email' => 'foreign-portal-request@example.test',
        ]);
        $this->apiGet('/api/v1/solicitudes', $clientUser)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $foreignRequest->id]);
        $this->apiGet('/api/v1/solicitudes/'.$created->json('data.id'), $clientUser)->assertOk();
        $this->apiGet('/api/v1/solicitudes/'.$foreignRequest->id, $clientUser)->assertForbidden();
        $this->apiPatch('/api/v1/solicitudes/'.$created->json('data.id'), $clientUser, [
            'estado' => 'atendida',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/solicitudes/'.$created->json('data.id'), $clientUser)->assertForbidden();

        self::assertSame('Nombre snapshot', SolicitudInformacion::query()->findOrFail($created->json('data.id'))->nombre);
    }

    public function test_create_validates_defaults_enums_foreign_keys_and_internal_fields(): void
    {
        $admin = $this->user('admin-request-validation@example.test', 'Administrador');

        $this->apiPost('/api/v1/solicitudes', $admin, [
            'email' => 'missing-name@example.test',
        ])->assertUnprocessable();
        $this->apiPost('/api/v1/solicitudes', $admin, [
            'nombre' => 'Tipo inválido',
            'email' => 'invalid-type@example.test',
            'medio_preferido' => 'sms',
        ])->assertUnprocessable();
        $this->apiPost('/api/v1/solicitudes', $admin, [
            'nombre' => 'FK inválida',
            'email' => 'invalid-fk@example.test',
            'cliente_id' => 999999,
        ])->assertUnprocessable();

        $deletedClient = $this->client('Cliente eliminado', 'deleted-request-client@example.test');
        $deletedClient->delete();
        $deletedProperty = $this->property('deleted-request-property');
        $deletedProperty->delete();
        $this->apiPost('/api/v1/solicitudes', $admin, [
            'nombre' => 'FK soft deleted',
            'email' => 'deleted-fk@example.test',
            'cliente_id' => $deletedClient->id,
            'inmueble_id' => $deletedProperty->id,
        ])->assertUnprocessable();

        foreach (['estado', 'origen', 'fecha_solicitud', 'fecha_atencion', 'atendida_por_user_id', 'id', 'roles'] as $field) {
            $this->apiPost('/api/v1/solicitudes', $admin, [
                'nombre' => 'Campo prohibido',
                'email' => 'prohibited-'.$field.'@example.test',
                $field => 'valor',
            ])->assertUnprocessable();
        }

        $created = $this->apiPost('/api/v1/solicitudes', $admin, [
            'nombre' => 'Default de solicitud',
            'email' => 'default-request@example.test',
        ])->assertCreated();
        $solicitud = SolicitudInformacion::query()->findOrFail($created->json('data.id'));

        self::assertSame('nueva', $solicitud->getRawOriginal('estado'));
        self::assertSame('registro_interno', $solicitud->getRawOriginal('origen'));
        self::assertNull($solicitud->atendida_por_user_id);
        self::assertNull($solicitud->fecha_atencion);
        self::assertNotNull($solicitud->fecha_solicitud);
    }

    public function test_update_validates_attendee_and_immutable_fields(): void
    {
        $admin = $this->user('admin-attendee@example.test', 'Administrador');
        $assistant = $this->user('assistant-attendee@example.test', 'Asistente');
        $validAgent = $this->agentUser('valid-attendee@example.test');
        $inactive = $this->user('inactive-attendee@example.test', 'Asistente');
        $inactive->update(['estado' => 'inactivo']);
        $softDeleted = $this->user('deleted-attendee@example.test', 'Asistente');
        $softDeleted->delete();
        $client = $this->user('client-attendee@example.test', 'Cliente');
        $solicitud = SolicitudInformacion::create([
            'nombre' => 'Asignación de atención',
            'email' => 'attendee-request@example.test',
        ]);

        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $assistant, [
            'atendida_por_user_id' => $validAgent->id,
        ])->assertOk()
            ->assertJsonPath('data.atendida_por_user_id', $validAgent->id);

        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
            'atendida_por_user_id' => null,
        ])->assertOk()
            ->assertJsonPath('data.atendida_por_user_id', null);

        foreach ([$inactive->id, $softDeleted->id, $client->id, $validAgent->id + 999999] as $attendeeId) {
            $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
                'atendida_por_user_id' => $attendeeId,
            ])->assertUnprocessable();
        }

        $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
            'estado' => 'estado-invalido',
        ])->assertUnprocessable();

        foreach (['cliente_id', 'inmueble_id', 'origen', 'fecha_solicitud', 'created_at', 'deleted_at'] as $field) {
            $this->apiPatch('/api/v1/solicitudes/'.$solicitud->id, $admin, [
                $field => 123,
            ])->assertUnprocessable();
        }
    }

    public function test_private_index_filters_are_applied_after_visibility_and_q_is_grouped(): void
    {
        $agent = $this->agentUser('agent-request-filter@example.test');
        $otherAgent = $this->agentUser('other-request-filter@example.test');
        $assignedClient = $this->client('Cliente visible filtro', 'visible-filter@example.test');
        $foreignClient = $this->client('Cliente secreto filtro', 'secret-filter@example.test');
        ClienteAgente::create(['cliente_id' => $assignedClient->id, 'agente_id' => $agent->agente->id]);
        ClienteAgente::create(['cliente_id' => $foreignClient->id, 'agente_id' => $otherAgent->agente->id]);
        SolicitudInformacion::create([
            'cliente_id' => $assignedClient->id,
            'nombre' => 'Visible',
            'email' => 'visible-filter@example.test',
            'estado' => 'en_atencion',
        ]);
        $foreign = SolicitudInformacion::create([
            'cliente_id' => $foreignClient->id,
            'nombre' => 'Visible solo en email externo',
            'email' => 'termino-secreto@example.test',
            'estado' => 'atendida',
        ]);

        $this->apiGet('/api/v1/solicitudes?q=termino-secreto', $agent)
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonMissing(['id' => $foreign->id]);
        $this->apiGet('/api/v1/solicitudes?estado=en_atencion&sort=nombre&direction=asc', $agent)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/solicitudes?per_page=101', $agent)->assertUnprocessable();
        $this->apiGet('/api/v1/solicitudes?sort=arbitrario', $agent)->assertUnprocessable();
        $this->apiGet('/api/v1/solicitudes?direction=random', $agent)->assertUnprocessable();
    }

    public function test_soft_deleted_request_is_not_listed_or_bound(): void
    {
        $admin = $this->user('admin-request-soft-delete@example.test', 'Administrador');
        $solicitud = SolicitudInformacion::create([
            'nombre' => 'Solicitud eliminada',
            'email' => 'deleted-request@example.test',
        ]);
        $solicitud->delete();

        $this->apiGet('/api/v1/solicitudes', $admin)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/solicitudes/'.$solicitud->id, $admin)->assertNotFound();
    }

    public function test_public_endpoint_creates_minimal_landing_request_without_side_effects(): void
    {
        $property = $this->property('public-request', ['publicado' => true]);
        $usersBefore = User::query()->count();
        $clientsBefore = Cliente::query()->count();

        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/public/solicitudes', [
                'nombre' => 'Visitante público',
                'email' => 'public@example.test',
                'telefono' => '5551234567',
                'mensaje' => 'Quiero conocer la propiedad.',
                'medio_preferido' => 'whatsapp',
                'inmueble_id' => $property->id,
            ])
            ->assertCreated()
            ->assertExactJson(['message' => 'Solicitud recibida correctamente.']);

        $solicitud = SolicitudInformacion::query()->firstOrFail();
        self::assertSame('landing_publica', $solicitud->getRawOriginal('origen'));
        self::assertSame('nueva', $solicitud->getRawOriginal('estado'));
        self::assertNull($solicitud->cliente_id);
        self::assertNull($solicitud->atendida_por_user_id);
        self::assertNull($solicitud->fecha_atencion);
        self::assertSame($usersBefore, User::query()->count());
        self::assertSame($clientsBefore, Cliente::query()->count());
    }

    public function test_public_endpoint_requires_published_property_and_rejects_internal_fields(): void
    {
        $private = $this->property('public-private');
        $deleted = $this->property('public-deleted', ['publicado' => true]);
        $deleted->delete();

        $base = [
            'nombre' => 'Visitante público',
            'email' => 'public-validation@example.test',
        ];

        $this->publicPost([...$base, 'inmueble_id' => $private->id])->assertUnprocessable();
        $this->publicPost([...$base, 'inmueble_id' => $deleted->id])->assertUnprocessable();

        foreach (['cliente_id', 'atendida_por_user_id', 'estado', 'origen', 'fecha_solicitud', 'fecha_atencion', 'roles'] as $field) {
            $this->publicPost([...$base, 'email' => 'public-'.$field.'@example.test', $field => 'valor'])
                ->assertUnprocessable();
        }

        $this->publicPost([...$base, 'medio_preferido' => 'sms'])->assertUnprocessable();
    }

    public function test_public_endpoint_is_rate_limited(): void
    {
        for ($index = 1; $index <= 10; $index++) {
            $this->publicPost([
                'nombre' => 'Rate limit '.$index,
                'email' => 'rate-'.$index.'@example.test',
            ])->assertCreated();
        }

        $this->publicPost([
            'nombre' => 'Rate limit excedido',
            'email' => 'rate-11@example.test',
        ])->assertStatus(429);
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
    ): Cliente {
        return Cliente::create([
            'user_id' => $user?->id,
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => $email,
            'estado_cliente' => 'prospecto',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function property(string $suffix, array $overrides = []): Inmueble
    {
        $owner = Propietario::create([
            'nombre_razon_social' => 'Owner '.$suffix,
            'rfc' => strtoupper(substr(md5('owner-'.$suffix), 0, 13)),
            'telefono' => '5555555555',
            'direccion' => 'Calle Propietario 1',
        ]);
        $category = Categoria::create(['nombre' => 'Category '.$suffix]);

        return Inmueble::create(array_merge([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'REQ-'.$suffix,
            'titulo' => 'Property '.$suffix,
            'slug' => 'request-'.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle Principal 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
        ], $overrides));
    }

    /** @param array<string, mixed> $payload */
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

    /** @param array<string, mixed> $payload */
    private function publicPost(array $payload): TestResponse
    {
        return $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/public/solicitudes', $payload);
    }
}
