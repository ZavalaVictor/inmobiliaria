<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\Inmueble;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CitasTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_private_appointments_require_authentication(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/citas')
            ->assertUnauthorized();
    }

    public function test_administrator_can_create_update_reprogram_change_state_list_history_and_delete(): void
    {
        $admin = $this->user('admin-citas@example.test', 'Administrador');
        [$client, $property, $agent] = $this->bookableSet('admin');

        $created = $this->apiPost('/api/v1/citas', $admin, [
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-01 10:00:00',
            'fecha_fin' => '2026-10-01 11:00:00',
            'motivo' => 'Visita',
        ])->assertCreated()
            ->assertJsonPath('data.estado', 'programada')
            ->assertJsonPath('data.creado_por_user_id', $admin->id)
            ->assertJsonMissingPath('data.agente.user.password');

        $cita = Cita::query()->findOrFail($created->json('data.id'));
        $this->apiGet('/api/v1/citas', $admin)->assertOk()->assertJsonCount(1, 'data');
        $this->apiPatch('/api/v1/citas/'.$cita->id, $admin, ['notas' => 'Confirmar visita'])
            ->assertOk()->assertJsonPath('data.notas', 'Confirmar visita');

        $this->apiPatch('/api/v1/citas/'.$cita->id.'/reprogramar', $admin, [
            'fecha_inicio' => '2026-10-01 12:00:00',
            'fecha_fin' => '2026-10-01 13:00:00',
            'motivo' => 'El cliente solicitó otro horario',
        ])->assertOk();

        $this->apiPatch('/api/v1/citas/'.$cita->id.'/estado', $admin, ['estado' => 'confirmada'])
            ->assertOk()->assertJsonPath('data.estado', 'confirmada');

        $this->apiGet('/api/v1/citas/'.$cita->id.'/historial', $admin)
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo_cambio', 'reprogramacion')
            ->assertJsonPath('data.0.modificado_por_user_id', $admin->id);

        $this->apiDelete('/api/v1/citas/'.$cita->id, $admin)->assertNoContent();
        $this->apiGet('/api/v1/citas/'.$cita->id, $admin)->assertNotFound();
        self::assertSoftDeleted('citas', ['id' => $cita->id]);
        self::assertDatabaseHas('cita_historial', ['cita_id' => $cita->id]);
    }

    public function test_assistant_can_manage_but_director_is_read_only(): void
    {
        $assistant = $this->user('assistant-citas@example.test', 'Asistente');
        $director = $this->user('director-citas@example.test', 'Director General');
        [$client, $property, $agent] = $this->bookableSet('roles');
        $cita = $this->createAppointment($assistant, $client, $property, $agent, '14:00:00');

        $this->apiGet('/api/v1/citas/'.$cita->id, $director)->assertOk();
        $this->apiGet('/api/v1/citas/'.$cita->id.'/historial', $director)->assertOk();
        $this->apiPatch('/api/v1/citas/'.$cita->id, $director, ['notas' => 'no'])->assertForbidden();
        $this->apiPost('/api/v1/citas', $director, $this->payload($client, $property, $agent, '15:00:00'))->assertForbidden();
        $this->apiDelete('/api/v1/citas/'.$cita->id, $assistant)->assertNoContent();
    }

    public function test_agent_must_use_own_profile_and_both_assignments(): void
    {
        $agentUser = $this->agentUser('agent-citas@example.test');
        [$client, $property, $agent] = $this->bookableSet('agent', $agentUser);

        $created = $this->apiPost('/api/v1/citas', $agentUser, [
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-02 10:00:00',
            'fecha_fin' => '2026-10-02 11:00:00',
        ])->assertCreated()->assertJsonPath('data.agente_id', $agent->id);

        $otherAgentUser = $this->agentUser('other-agent-citas@example.test');
        $this->apiPost('/api/v1/citas', $agentUser, [
            'cliente_id' => $client->id,
            'agente_id' => $otherAgentUser->agente->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-02 12:00:00',
            'fecha_fin' => '2026-10-02 13:00:00',
        ])->assertUnprocessable();

        $foreignClient = $this->client('Cliente fuera de alcance');
        $this->apiPost('/api/v1/citas', $agentUser, [
            'cliente_id' => $foreignClient->id,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-02 12:00:00',
            'fecha_fin' => '2026-10-02 13:00:00',
        ])->assertUnprocessable();

        $this->apiGet('/api/v1/citas/'.$created->json('data.id'), $agentUser)->assertOk();
        $this->apiPatch('/api/v1/citas/'.$created->json('data.id').'/reprogramar', $agentUser, [
            'fecha_inicio' => '2026-10-02 11:00:00',
            'fecha_fin' => '2026-10-02 12:00:00',
            'motivo' => 'Cambio del cliente',
            'agente_id' => $agent->id,
        ])->assertUnprocessable();
        $this->apiDelete('/api/v1/citas/'.$created->json('data.id'), $agentUser)->assertForbidden();
    }

    public function test_overlap_is_checked_for_agent_and_property_with_half_open_boundaries(): void
    {
        $admin = $this->user('overlap-admin@example.test', 'Administrador');
        [$client, $property, $agent] = $this->bookableSet('overlap');
        $this->createAppointment($admin, $client, $property, $agent, '10:00:00', '11:00:00');

        $this->apiPost('/api/v1/citas', $admin, $this->payload($client, $property, $agent, '10:30:00', '11:30:00'))->assertUnprocessable();
        $this->apiPost('/api/v1/citas', $admin, $this->payload($client, $property, $agent, '11:00:00', '12:00:00'))->assertCreated();

        $otherAgent = $this->agentUser('overlap-other@example.test');
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $otherAgent->agente->id]);
        AgenteInmueble::create(['inmueble_id' => $property->id, 'agente_id' => $otherAgent->agente->id]);
        $this->apiPost('/api/v1/citas', $admin, $this->payload($client, $property, $otherAgent->agente->id, '10:15:00', '10:45:00'))
            ->assertUnprocessable();
    }

    public function test_non_blocking_appointments_do_not_conflict_and_reactivation_checks_conflicts(): void
    {
        $admin = $this->user('states-admin@example.test', 'Administrador');
        [$client, $property, $agent] = $this->bookableSet('states');
        $first = $this->createAppointment($admin, $client, $property, $agent, '09:00:00', '10:00:00');
        $this->apiPatch('/api/v1/citas/'.$first->id.'/estado', $admin, ['estado' => 'cancelada'])->assertOk();
        $second = $this->apiPost('/api/v1/citas', $admin, $this->payload($client, $property, $agent, '09:30:00', '10:30:00'))->assertCreated();

        $this->apiPatch('/api/v1/citas/'.$first->id.'/estado', $admin, ['estado' => 'confirmada'])
            ->assertUnprocessable();
        self::assertSame('cancelada', Cita::withTrashed()->findOrFail($first->id)->estado->value);
        $this->apiPatch('/api/v1/citas/'.$second->json('data.id').'/estado', $admin, ['estado' => 'completada'])->assertOk();
    }

    public function test_reprogramming_records_agent_change_and_is_idempotent(): void
    {
        $admin = $this->user('history-admin@example.test', 'Administrador');
        [$client, $property, $agent] = $this->bookableSet('history');
        $otherUser = $this->agentUser('history-other@example.test');
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $otherUser->agente->id]);
        AgenteInmueble::create(['inmueble_id' => $property->id, 'agente_id' => $otherUser->agente->id]);
        $cita = $this->createAppointment($admin, $client, $property, $agent, '10:00:00', '11:00:00');

        $this->apiPatch('/api/v1/citas/'.$cita->id.'/reprogramar', $admin, [
            'fecha_inicio' => '2026-10-03 10:00:00',
            'fecha_fin' => '2026-10-03 11:00:00',
            'agente_id' => $otherUser->agente->id,
            'motivo' => 'Reasignación y horario',
        ])->assertOk();
        self::assertDatabaseHas('cita_historial', [
            'cita_id' => $cita->id,
            'tipo_cambio' => 'reprogramacion_y_agente',
            'agente_anterior_id' => $agent->id,
            'agente_nuevo_id' => $otherUser->agente->id,
            'motivo' => 'Reasignación y horario',
            'modificado_por_user_id' => $admin->id,
        ]);

        $count = Cita::findOrFail($cita->id)->historial()->count();
        $this->apiPatch('/api/v1/citas/'.$cita->id.'/reprogramar', $admin, [
            'fecha_inicio' => '2026-10-03 10:00:00',
            'fecha_fin' => '2026-10-03 11:00:00',
            'agente_id' => $otherUser->agente->id,
            'motivo' => 'Reintento idempotente',
        ])->assertOk();
        self::assertSame($count, Cita::findOrFail($cita->id)->historial()->count());
    }

    public function test_validation_prohibits_internal_fields_and_opportunity_must_match_client_and_property(): void
    {
        $admin = $this->user('validation-citas@example.test', 'Administrador');
        [$client, $property, $agent] = $this->bookableSet('validation');
        $otherClient = $this->client('Cliente diferente citas');
        $opportunity = Oportunidad::create(['cliente_id' => $client->id, 'inmueble_id' => $property->id, 'titulo' => 'Oportunidad cita']);

        $this->apiPost('/api/v1/citas', $admin, array_merge(
            $this->payload($client, $property, $agent, '16:00:00'),
            ['estado' => 'confirmada', 'creado_por_user_id' => $otherClient->id]
        ))->assertUnprocessable();

        $this->apiPost('/api/v1/citas', $admin, array_merge(
            $this->payload($otherClient, $property, $agent, '16:00:00'),
            ['oportunidad_id' => $opportunity->id]
        ))->assertUnprocessable();
    }

    public function test_soft_deleted_and_terminal_appointments_do_not_block(): void
    {
        $admin = $this->user('soft-delete-citas@example.test', 'Administrador');
        [$client, $property, $agent] = $this->bookableSet('soft');
        $cita = $this->createAppointment($admin, $client, $property, $agent, '17:00:00', '18:00:00');
        $cita->delete();

        $this->apiPost('/api/v1/citas', $admin, $this->payload($client, $property, $agent, '17:30:00', '18:30:00'))->assertCreated();
    }

    protected function bookableSet(string $suffix, ?User $agentUser = null): array
    {
        $agentUser ??= $this->agentUser('agent-'.$suffix.'@example.test');
        $client = $this->client('Cliente '.$suffix);
        $property = $this->property('CITA-'.strtoupper($suffix));
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agentUser->agente->id]);
        AgenteInmueble::create(['inmueble_id' => $property->id, 'agente_id' => $agentUser->agente->id]);

        return [$client, $property, $agentUser->agente];
    }

    protected function createAppointment(User $creator, Cliente $client, Inmueble $property, int|Agente $agent, string $start, ?string $end = null): Cita
    {
        $agentId = $agent instanceof Agente ? $agent->id : $agent;
        $end ??= Carbon::parse('2026-10-01 '.$start)->addHour()->format('H:i:s');

        return Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => $agentId,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $creator->id,
            'fecha_inicio' => '2026-10-01 '.$start,
            'fecha_fin' => '2026-10-01 '.$end,
            'estado' => 'programada',
        ]);
    }

    protected function payload(Cliente $client, Inmueble $property, int|Agente $agent, string $start, ?string $end = null): array
    {
        $agentId = $agent instanceof Agente ? $agent->id : $agent;
        $end ??= Carbon::parse('2026-10-01 '.$start)->addHour()->format('H:i:s');

        return [
            'cliente_id' => $client->id,
            'agente_id' => $agentId,
            'inmueble_id' => $property->id,
            'fecha_inicio' => '2026-10-01 '.$start,
            'fecha_fin' => '2026-10-01 '.$end,
        ];
    }

    protected function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Citas',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    protected function agentUser(string $email, string $state = 'activo'): User
    {
        $user = $this->user($email, 'Agente Inmobiliario');
        $sequence = ++self::$sequence;
        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => 'CIT-'.$sequence,
            'estado_laboral' => $state,
        ]);

        return $user->fresh('agente');
    }

    protected function client(string $name): Cliente
    {
        return Cliente::create([
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => 'client-'.(++self::$sequence).'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    protected function property(string $code): Inmueble
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
        ]);
    }

    protected function apiGet(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->getJson($uri);
    }

    protected function apiPost(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->postJson($uri, $payload);
    }

    protected function apiPatch(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->patchJson($uri, $payload);
    }

    protected function apiDelete(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->deleteJson($uri);
    }
}
