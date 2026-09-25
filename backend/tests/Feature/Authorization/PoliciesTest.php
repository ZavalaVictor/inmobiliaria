<?php

namespace Tests\Feature\Authorization;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\CategoriaDocumento;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\ClienteInmuebleInteres;
use App\Models\Documento;
use App\Models\HistorialCorreo;
use App\Models\Inmueble;
use App\Models\InmuebleImagen;
use App\Models\Operacion;
use App\Models\OperacionAgente;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Policies\AgenteInmueblePolicy;
use App\Policies\AgentePolicy;
use App\Policies\CitaPolicy;
use App\Policies\ClienteAgentePolicy;
use App\Policies\ClienteInmuebleInteresPolicy;
use App\Policies\ClientePolicy;
use App\Policies\DocumentoPolicy;
use App\Policies\HistorialCorreoPolicy;
use App\Policies\InmuebleImagenPolicy;
use App\Policies\InmueblePolicy;
use App\Policies\OperacionPolicy;
use App\Policies\OportunidadPolicy;
use App\Policies\PropietarioPolicy;
use App\Policies\SolicitudInformacionPolicy;
use App\Policies\UserPolicy;
use App\Queries\Visibility\VisibleCitasQuery;
use App\Queries\Visibility\VisibleClientesQuery;
use App\Queries\Visibility\VisibleDocumentosQuery;
use App\Queries\Visibility\VisibleHistorialCorreosQuery;
use App\Queries\Visibility\VisibleInmueblesQuery;
use App\Queries\Visibility\VisibleOperacionesQuery;
use App\Queries\Visibility\VisibleOportunidadesQuery;
use App\Queries\Visibility\VisiblePropietariosQuery;
use App\Queries\Visibility\VisibleSolicitudesQuery;
use App\Support\Authorization\ActorScope;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_agent_and_client_scopes_are_enforced_for_clients(): void
    {
        $agentA = $this->agentUser('agent-a@example.test');
        $agentB = $this->agentUser('agent-b@example.test');
        $clientA = $this->client($this->user('client-a@example.test', 'Cliente'));
        $clientB = $this->client($this->user('client-b@example.test', 'Cliente'));

        ClienteAgente::create(['cliente_id' => $clientA->id, 'agente_id' => $agentA->agente->id]);

        self::assertTrue(Gate::forUser($agentA)->allows('view', $clientA));
        self::assertFalse(Gate::forUser($agentA)->allows('view', $clientB));
        self::assertTrue(Gate::forUser($clientA->user)->allows('view', $clientA));
        self::assertFalse(Gate::forUser($clientA->user)->allows('view', $clientB));
        self::assertTrue(app(ClientePolicy::class)->view($clientA->user, $clientA));
    }

    public function test_property_and_owner_scope_is_enforced(): void
    {
        $agent = $this->agentUser('agent@example.test');
        $otherAgent = $this->agentUser('other-agent@example.test');
        $owner = $this->owner('RFCOWNER001');
        $otherOwner = $this->owner('RFCOWNER002');
        $property = $this->property($owner, 'PROP-001');
        $otherProperty = $this->property($otherOwner, 'PROP-002');

        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);

        self::assertTrue(Gate::forUser($agent)->allows('view', $property));
        self::assertFalse(Gate::forUser($agent)->allows('view', $otherProperty));
        self::assertTrue(Gate::forUser($agent)->allows('view', $owner));
        self::assertFalse(Gate::forUser($otherAgent)->allows('view', $owner));
        self::assertTrue(app(PropietarioPolicy::class)->view($agent, $owner));
    }

    public function test_client_can_only_view_related_private_property(): void
    {
        $clientUser = $this->user('client@example.test', 'Cliente');
        $client = $this->client($clientUser);
        $owner = $this->owner('RFCOWNER003');
        $category = $this->category();
        $related = $this->property($owner, 'PROP-003', $category);
        $unrelated = $this->property($owner, 'PROP-004', $category);

        ClienteInmuebleInteres::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $related->id,
            'nivel_interes' => 'alto',
        ]);

        self::assertTrue(Gate::forUser($clientUser)->allows('view', $related));
        self::assertFalse(Gate::forUser($clientUser)->allows('view', $unrelated));
        self::assertTrue(app(InmueblePolicy::class)->view($clientUser, $related));
    }

    public function test_interest_reads_use_or_but_mutations_require_both_assignments(): void
    {
        $agent = $this->agentUser('interest-agent@example.test');
        $client = $this->client($this->user('interest-client@example.test', 'Cliente'));
        $property = $this->property($this->owner('RFCOWNER005'), 'PROP-005');
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        $interest = ClienteInmuebleInteres::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
        ]);

        $policy = app(ClienteInmuebleInteresPolicy::class);

        self::assertTrue($policy->view($agent, $interest));
        self::assertFalse($policy->update($agent, $interest));

        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);

        self::assertTrue($policy->update($agent, $interest));
        self::assertTrue($policy->delete($agent, $interest));
    }

    public function test_opportunity_principal_and_indirect_access_have_different_write_scope(): void
    {
        $agent = $this->agentUser('opportunity-agent@example.test');
        $otherAgent = $this->agentUser('opportunity-other@example.test');
        $client = $this->client($this->user('opportunity-client@example.test', 'Cliente'));
        $property = $this->property($this->owner('RFCOWNER006'), 'PROP-006');
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);

        $indirect = Oportunidad::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'agente_principal_id' => $otherAgent->agente->id,
            'titulo' => 'Oportunidad indirecta',
        ]);
        $principal = Oportunidad::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'agente_principal_id' => $agent->agente->id,
            'titulo' => 'Oportunidad principal',
        ]);

        $policy = app(OportunidadPolicy::class);

        self::assertTrue($policy->view($agent, $indirect));
        self::assertFalse($policy->update($agent, $indirect));
        self::assertTrue($policy->update($agent, $principal));
    }

    public function test_appointments_are_limited_to_their_agent_or_client(): void
    {
        $agent = $this->agentUser('appointment-agent@example.test');
        $otherAgent = $this->agentUser('appointment-other@example.test');
        $client = $this->client($this->user('appointment-client@example.test', 'Cliente'));
        $otherClient = $this->client($this->user('appointment-other-client@example.test', 'Cliente'));
        $property = $this->property($this->owner('RFCOWNER007'), 'PROP-007');
        $appointment = Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => $agent->agente->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $agent->id,
            'fecha_inicio' => '2026-10-01 10:00:00',
            'fecha_fin' => '2026-10-01 11:00:00',
        ]);
        $otherAppointment = Cita::create([
            'cliente_id' => $otherClient->id,
            'agente_id' => $otherAgent->agente->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $otherAgent->id,
            'fecha_inicio' => '2026-10-02 10:00:00',
            'fecha_fin' => '2026-10-02 11:00:00',
        ]);
        $policy = app(CitaPolicy::class);

        self::assertTrue($policy->view($agent, $appointment));
        self::assertFalse($policy->view($agent, $otherAppointment));
        self::assertTrue($policy->view($client->user, $appointment));
        self::assertFalse($policy->view($client->user, $otherAppointment));
    }

    public function test_agent_sees_operations_only_through_operation_assignment(): void
    {
        $agent = $this->agentUser('operation-agent@example.test');
        $otherAgent = $this->agentUser('operation-other@example.test');
        $client = $this->client($this->user('operation-client@example.test', 'Cliente'));
        $property = $this->property($this->owner('RFCOWNER008'), 'PROP-008');
        $opportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'titulo' => 'Oportunidad de operación',
        ]);
        $operation = Operacion::create([
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'registrado_por_user_id' => $agent->id,
            'tipo_operacion' => 'venta',
            'monto' => 100000,
        ]);
        OperacionAgente::create(['operacion_id' => $operation->id, 'agente_id' => $agent->agente->id]);
        $policy = app(OperacionPolicy::class);

        self::assertTrue($policy->view($agent, $operation));
        self::assertFalse($policy->view($otherAgent, $operation));
        self::assertFalse($policy->view($client->user, $operation));
    }

    public function test_documents_use_their_single_persisted_destination(): void
    {
        $agent = $this->agentUser('document-agent@example.test');
        $otherAgent = $this->agentUser('document-other@example.test');
        $client = $this->client($this->user('document-client@example.test', 'Cliente'));
        $owner = $this->owner('RFCOWNER009');
        $property = $this->property($owner, 'PROP-009');
        $category = CategoriaDocumento::create(['nombre' => 'Contrato']);
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);
        $opportunity = Oportunidad::create(['cliente_id' => $client->id, 'titulo' => 'Documento opportunity']);
        $operation = Operacion::create([
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'registrado_por_user_id' => $agent->id,
            'tipo_operacion' => 'venta',
            'monto' => 120000,
        ]);
        OperacionAgente::create(['operacion_id' => $operation->id, 'agente_id' => $agent->agente->id]);

        $clientDocument = $this->document($category, $agent, ['cliente_id' => $client->id]);
        $propertyDocument = $this->document($category, $agent, ['inmueble_id' => $property->id]);
        $ownerDocument = $this->document($category, $agent, ['propietario_id' => $owner->id]);
        $operationDocument = $this->document($category, $agent, ['operacion_id' => $operation->id]);
        $policy = app(DocumentoPolicy::class);

        self::assertTrue($policy->view($agent, $clientDocument));
        self::assertTrue($policy->view($agent, $propertyDocument));
        self::assertTrue($policy->view($agent, $ownerDocument));
        self::assertTrue($policy->view($agent, $operationDocument));
        self::assertFalse($policy->view($otherAgent, $clientDocument));
        self::assertFalse($policy->view($client->user, $clientDocument));

        $invalid = new Documento(['cliente_id' => $client->id, 'inmueble_id' => $property->id]);
        self::assertFalse($policy->view($agent, $invalid));
    }

    public function test_documents_are_not_available_to_assistant_or_director(): void
    {
        $assistant = $this->user('assistant@example.test', 'Asistente');
        $director = $this->user('director@example.test', 'Director General');
        $category = CategoriaDocumento::create(['nombre' => 'Identificación']);
        $owner = $this->owner('RFCOWNER010');
        $document = $this->document($category, $assistant, ['propietario_id' => $owner->id]);
        $policy = app(DocumentoPolicy::class);

        self::assertFalse($policy->view($assistant, $document));
        self::assertFalse($policy->view($director, $document));
    }

    public function test_requests_without_entities_are_visible_only_to_the_attending_user(): void
    {
        $agent = $this->agentUser('request-agent@example.test');
        $otherAgent = $this->agentUser('request-other@example.test');
        $attended = SolicitudInformacion::create([
            'atendida_por_user_id' => $agent->id,
            'nombre' => 'Solicitud atendida',
            'email' => 'request@example.test',
        ]);
        $unassigned = SolicitudInformacion::create([
            'nombre' => 'Solicitud sin relación',
            'email' => 'unassigned@example.test',
        ]);
        $policy = app(SolicitudInformacionPolicy::class);

        self::assertTrue($policy->view($agent, $attended));
        self::assertFalse($policy->view($otherAgent, $attended));
        self::assertFalse($policy->view($agent, $unassigned));
    }

    public function test_agent_can_only_view_related_email_history(): void
    {
        $agent = $this->agentUser('email-agent@example.test');
        $otherAgent = $this->agentUser('email-other@example.test');
        $client = $this->client($this->user('email-client@example.test', 'Cliente'));
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        $related = HistorialCorreo::create([
            'cliente_id' => $client->id,
            'destinatario_email' => 'client@example.test',
            'tipo' => 'otro',
            'asunto' => 'Relacionado',
        ]);
        $unrelated = HistorialCorreo::create([
            'destinatario_email' => 'private@example.test',
            'tipo' => 'seguridad',
            'asunto' => 'No relacionado',
        ]);
        $policy = app(HistorialCorreoPolicy::class);

        self::assertTrue($policy->view($agent, $related));
        self::assertFalse($policy->view($agent, $unrelated));
        self::assertFalse($policy->view($otherAgent, $related));
    }

    public function test_visibility_queries_filter_agent_and_client_listings(): void
    {
        $agent = $this->agentUser('query-agent@example.test');
        $otherAgent = $this->agentUser('query-other@example.test');
        $client = $this->client($this->user('query-client@example.test', 'Cliente'));
        $otherClient = $this->client($this->user('query-other-client@example.test', 'Cliente'));
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        $owner = $this->owner('RFCOWNER011');
        $property = $this->property($owner, 'PROP-011');
        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);
        ClienteInmuebleInteres::create(['cliente_id' => $client->id, 'inmueble_id' => $property->id]);

        $agentClients = (new VisibleClientesQuery($agent))->apply(Cliente::query())->pluck('id');
        self::assertSame([$client->id], $agentClients->all());

        $clientProperties = (new VisibleInmueblesQuery($client->user))->apply(Inmueble::query())->pluck('id');
        self::assertSame([$property->id], $clientProperties->all());

        ClienteAgente::where('cliente_id', $client->id)->where('agente_id', $agent->agente->id)->delete();
        self::assertSame([], (new VisibleClientesQuery($agent))->apply(Cliente::query())->pluck('id')->all());

        self::assertNotContains($otherClient->id, $agentClients->all());
        self::assertNotContains($otherAgent->id, $agentClients->all());
    }

    public function test_users_without_roles_or_with_unknown_roles_are_not_global(): void
    {
        $unassigned = User::create([
            'nombres' => 'Sin',
            'apellido_paterno' => 'Rol',
            'email' => 'without-role@example.test',
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $unassigned->givePermissionTo('clientes.ver');

        $customRole = Role::create(['name' => 'Auditor Externo', 'guard_name' => 'web']);
        $customRole->givePermissionTo('clientes.ver');
        $custom = $this->user('custom-role@example.test', 'Auditor Externo');

        self::assertFalse(ActorScope::isGlobal($unassigned));
        self::assertFalse(ActorScope::isGlobal($custom));
        self::assertFalse(app(ClientePolicy::class)->viewAny($unassigned));
        self::assertFalse(app(ClientePolicy::class)->viewAny($custom));
        self::assertSame([], (new VisibleClientesQuery($unassigned))->apply(Cliente::query())->pluck('id')->all());
        self::assertSame([], (new VisibleClientesQuery($custom))->apply(Cliente::query())->pluck('id')->all());
    }

    public function test_agent_and_client_without_profiles_fail_closed_in_policies_and_queries(): void
    {
        $orphanAgent = $this->user('orphan-agent@example.test', 'Agente Inmobiliario');
        $orphanClient = $this->user('orphan-client@example.test', 'Cliente');

        self::assertFalse(app(ClientePolicy::class)->viewAny($orphanAgent));
        self::assertFalse(app(InmueblePolicy::class)->viewAny($orphanAgent));
        self::assertFalse(app(PropietarioPolicy::class)->viewAny($orphanAgent));
        self::assertFalse(app(OportunidadPolicy::class)->viewAny($orphanAgent));
        self::assertFalse(app(CitaPolicy::class)->viewAny($orphanAgent));
        self::assertFalse(app(OperacionPolicy::class)->viewAny($orphanAgent));
        self::assertFalse(app(DocumentoPolicy::class)->viewAny($orphanAgent));
        self::assertFalse(app(SolicitudInformacionPolicy::class)->viewAny($orphanAgent));
        self::assertFalse(app(HistorialCorreoPolicy::class)->viewAny($orphanAgent));

        self::assertFalse(app(ClientePolicy::class)->viewAny($orphanClient));
        self::assertFalse(app(InmueblePolicy::class)->viewAny($orphanClient));
        self::assertFalse(app(CitaPolicy::class)->viewAny($orphanClient));
        self::assertFalse(app(SolicitudInformacionPolicy::class)->viewAny($orphanClient));

        $queries = [
            fn () => (new VisibleClientesQuery($orphanAgent))->apply(Cliente::query()),
            fn () => (new VisibleInmueblesQuery($orphanAgent))->apply(Inmueble::query()),
            fn () => (new VisiblePropietariosQuery($orphanAgent))->apply(Propietario::query()),
            fn () => (new VisibleOportunidadesQuery($orphanAgent))->apply(Oportunidad::query()),
            fn () => (new VisibleCitasQuery($orphanAgent))->apply(Cita::query()),
            fn () => (new VisibleOperacionesQuery($orphanAgent))->apply(Operacion::query()),
            fn () => (new VisibleDocumentosQuery($orphanAgent))->apply(Documento::query()),
            fn () => (new VisibleSolicitudesQuery($orphanAgent))->apply(SolicitudInformacion::query()),
            fn () => (new VisibleHistorialCorreosQuery($orphanAgent))->apply(HistorialCorreo::query()),
        ];

        foreach ($queries as $query) {
            self::assertSame(0, $query()->count());
        }
    }

    public function test_user_policy_assign_roles_requires_its_specific_permission(): void
    {
        $administrator = $this->user('role-admin@example.test', 'Administrador');
        $agent = $this->user('role-agent@example.test', 'Agente Inmobiliario');
        $target = $this->user('role-target@example.test', 'Cliente');
        $policy = app(UserPolicy::class);

        self::assertTrue($policy->assignRoles($administrator, $target));
        self::assertFalse($policy->assignRoles($agent, $target));
    }

    public function test_valid_scope_is_rejected_when_its_permission_is_missing(): void
    {
        $agent = $this->agentUser('missing-permission@example.test');
        $client = $this->client($this->user('missing-permission-client@example.test', 'Cliente'));
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);

        Role::findByName('Agente Inmobiliario', 'web')->revokePermissionTo('clientes.ver');

        self::assertFalse(app(ClientePolicy::class)->view($agent, $client));
        self::assertSame([], (new VisibleClientesQuery($agent))->apply(Cliente::query())->pluck('id')->all());
    }

    public function test_invalid_documents_are_rejected_for_global_users_and_filtered_from_queries(): void
    {
        $administrator = $this->user('invalid-document-admin@example.test', 'Administrador');
        $zeroDestinations = new Documento;
        $multipleDestinations = new Documento([
            'cliente_id' => 10,
            'inmueble_id' => 20,
        ]);
        $policy = app(DocumentoPolicy::class);

        self::assertFalse($policy->view($administrator, $zeroDestinations));
        self::assertFalse($policy->view($administrator, $multipleDestinations));

        $query = (new VisibleDocumentosQuery($administrator))->apply(Documento::query());

        self::assertStringContainsString('propietario_id IS NOT NULL', $query->toSql());
        self::assertStringContainsString('operacion_id IS NOT NULL', $query->toSql());
    }

    public function test_client_cannot_delete_own_record_even_if_permission_is_added(): void
    {
        $clientUser = $this->user('client-delete@example.test', 'Cliente');
        $client = $this->client($clientUser);
        $clientUser->givePermissionTo('clientes.eliminar');

        self::assertFalse(app(ClientePolicy::class)->delete($clientUser, $client));
    }

    public function test_derived_assignment_and_image_policies_enforce_basic_scope(): void
    {
        $agent = $this->agentUser('derived-agent@example.test');
        $otherAgent = $this->agentUser('derived-other@example.test');
        $client = $this->client($this->user('derived-client@example.test', 'Cliente'));
        $property = $this->property($this->owner('RFCOWNER012'), 'PROP-012');
        $image = InmuebleImagen::create([
            'inmueble_id' => $property->id,
            'firebase_path' => 'images/derived.jpg',
        ]);
        $agentAssignment = AgenteInmueble::create([
            'agente_id' => $agent->agente->id,
            'inmueble_id' => $property->id,
        ]);
        $clientAssignment = ClienteAgente::create([
            'cliente_id' => $client->id,
            'agente_id' => $agent->agente->id,
        ]);

        self::assertTrue(app(InmuebleImagenPolicy::class)->view($agent, $image));
        self::assertFalse(app(InmuebleImagenPolicy::class)->view($otherAgent, $image));
        self::assertTrue(app(AgenteInmueblePolicy::class)->view($agent, $agentAssignment));
        self::assertFalse(app(AgenteInmueblePolicy::class)->view($otherAgent, $agentAssignment));
        self::assertTrue(app(ClienteAgentePolicy::class)->view($agent, $clientAssignment));
        self::assertTrue(app(ClienteAgentePolicy::class)->view($client->user, $clientAssignment));
    }

    public function test_email_history_allows_each_approved_agent_relation_only(): void
    {
        $agent = $this->agentUser('email-relations-agent@example.test');
        $otherAgent = $this->agentUser('email-relations-other@example.test');
        $client = $this->client($this->user('email-relations-client@example.test', 'Cliente'));
        $property = $this->property($this->owner('RFCOWNER013'), 'PROP-013');
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        $appointment = Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => $agent->agente->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $agent->id,
            'fecha_inicio' => '2026-10-03 10:00:00',
            'fecha_fin' => '2026-10-03 11:00:00',
        ]);
        $policy = app(HistorialCorreoPolicy::class);
        $relatedClient = HistorialCorreo::create([
            'cliente_id' => $client->id,
            'destinatario_email' => 'client-relation@example.test',
            'tipo' => 'otro',
            'asunto' => 'Cliente',
        ]);
        $relatedAppointment = HistorialCorreo::create([
            'cita_id' => $appointment->id,
            'destinatario_email' => 'appointment-relation@example.test',
            'tipo' => 'confirmacion_cita',
            'asunto' => 'Cita',
        ]);
        $relatedRecipient = HistorialCorreo::create([
            'destinatario_user_id' => $agent->id,
            'destinatario_email' => 'recipient-relation@example.test',
            'tipo' => 'otro',
            'asunto' => 'Destinatario',
        ]);
        $relatedSender = HistorialCorreo::create([
            'enviado_por_user_id' => $agent->id,
            'destinatario_email' => 'sender-relation@example.test',
            'tipo' => 'otro',
            'asunto' => 'Remitente',
        ]);
        $unrelated = HistorialCorreo::create([
            'destinatario_email' => 'unrelated@example.test',
            'tipo' => 'seguridad',
            'asunto' => 'Ajeno',
        ]);

        self::assertTrue($policy->view($agent, $relatedClient));
        self::assertTrue($policy->view($agent, $relatedAppointment));
        self::assertTrue($policy->view($agent, $relatedRecipient));
        self::assertTrue($policy->view($agent, $relatedSender));
        self::assertFalse($policy->view($otherAgent, $relatedClient));
        self::assertFalse($policy->view($agent, $unrelated));
    }

    public function test_multi_role_user_is_global_when_holding_an_approved_global_role(): void
    {
        $user = $this->user('multi-role@example.test', 'Administrador');
        $user->assignRole('Agente Inmobiliario');

        self::assertTrue(ActorScope::isGlobal($user));
        self::assertTrue($user->can('clientes.ver'));
    }

    public function test_restore_and_force_delete_are_not_policy_abilities(): void
    {
        $policyClasses = [
            AgentePolicy::class,
            CitaPolicy::class,
            ClientePolicy::class,
            DocumentoPolicy::class,
            HistorialCorreoPolicy::class,
            InmueblePolicy::class,
            OperacionPolicy::class,
            OportunidadPolicy::class,
            PropietarioPolicy::class,
            SolicitudInformacionPolicy::class,
        ];

        foreach ($policyClasses as $policyClass) {
            self::assertFalse(method_exists($policyClass, 'restore'));
            self::assertFalse(method_exists($policyClass, 'forceDelete'));
        }
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

        return $user;
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

    private function client(User $user): Cliente
    {
        return Cliente::create([
            'user_id' => $user->id,
            'nombres' => 'Cliente',
            'apellido_paterno' => 'Prueba',
            'estado_cliente' => 'cliente',
        ])->load('user');
    }

    private function owner(string $rfc): Propietario
    {
        return Propietario::create([
            'tipo_persona' => 'fisica',
            'nombre_razon_social' => 'Propietario de prueba',
            'rfc' => $rfc,
            'telefono' => '5555555555',
            'direccion' => 'Dirección de prueba',
        ]);
    }

    private function category(): Categoria
    {
        return Categoria::create(['nombre' => 'Residencial '.uniqid()]);
    }

    private function property(Propietario $owner, string $code, ?Categoria $category = null): Inmueble
    {
        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => ($category ?? $this->category())->id,
            'codigo' => $code,
            'titulo' => 'Inmueble de prueba',
            'slug' => strtolower($code).'-slug',
            'tipo_operacion' => 'venta',
            'precio_venta' => 100000,
            'calle' => 'Calle de prueba',
            'colonia' => 'Colonia de prueba',
            'municipio' => 'Municipio de prueba',
            'estado_ubicacion' => 'Estado de prueba',
            'codigo_postal' => '01000',
        ]);
    }

    /** @param array<string, mixed> $destination */
    private function document(CategoriaDocumento $category, User $uploader, array $destination): Documento
    {
        return Documento::create(array_merge([
            'categoria_documento_id' => $category->id,
            'subido_por_user_id' => $uploader->id,
            'nombre_original' => 'documento.pdf',
            'firebase_path' => 'documents/documento.pdf',
            'mime_type' => 'application/pdf',
        ], $destination));
    }
}
