<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\InteraccionCliente;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InteraccionesClienteTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_interactions(): void
    {
        $cliente = $this->client('No autenticado');

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson($this->interactionUrl($cliente))
            ->assertUnauthorized();
    }

    public function test_administrator_can_manage_interaction_and_is_registered_as_author(): void
    {
        $admin = $this->user('admin-interactions@example.test', 'Administrador');
        $cliente = $this->client('Administrado');

        $created = $this->apiPost($this->interactionUrl($cliente), $admin, [
            'tipo' => 'llamada',
            'descripcion' => 'Llamada de seguimiento',
            'resultado' => 'Interesado',
            'fecha_interaccion' => '2026-09-20 10:30:00',
            'proxima_accion' => 'Enviar propuesta',
            'fecha_proxima_accion' => '2026-09-22 09:00:00',
        ])->assertCreated()
            ->assertJsonPath('data.tipo', 'llamada')
            ->assertJsonPath('data.registrado_por_user_id', $admin->id)
            ->assertJsonPath('data.fecha_interaccion', '2026-09-20T10:30:00.000000Z')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.roles')
            ->assertJsonMissingPath('data.deleted_at');
        $interaction = InteraccionCliente::query()->findOrFail($created->json('data.id'));

        $this->apiGet($this->interactionUrl($cliente), $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet($this->interactionUrl($cliente).'/'.$interaction->id, $admin)->assertOk();
        $this->apiPatch($this->interactionUrl($cliente).'/'.$interaction->id, $admin, [
            'resultado' => 'Propuesta enviada',
            'descripcion' => 'Se actualizó la interacción',
        ])->assertOk();
        $this->apiDelete($this->interactionUrl($cliente).'/'.$interaction->id, $admin)->assertNoContent();

        self::assertNotNull($interaction->fresh()->deleted_at);
        self::assertDatabaseHas('clientes', ['id' => $cliente->id]);
        self::assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_assistant_can_read_create_update_but_cannot_delete(): void
    {
        $assistant = $this->user('assistant-interactions@example.test', 'Asistente');
        $cliente = $this->client('Asistente');

        $created = $this->apiPost($this->interactionUrl($cliente), $assistant, [
            'tipo' => 'nota',
            'descripcion' => 'Nota administrativa',
        ])->assertCreated()
            ->assertJsonPath('data.registrado_por_user_id', $assistant->id);
        $interaction = InteraccionCliente::query()->findOrFail($created->json('data.id'));

        $this->apiGet($this->interactionUrl($cliente), $assistant)->assertOk();
        $this->apiPatch($this->interactionUrl($cliente).'/'.$interaction->id, $assistant, [
            'tipo' => 'seguimiento',
        ])->assertOk();
        $this->apiDelete($this->interactionUrl($cliente).'/'.$interaction->id, $assistant)->assertForbidden();
    }

    public function test_director_can_only_read_interactions(): void
    {
        $director = $this->user('director-interactions@example.test', 'Director General');
        $cliente = $this->client('Director');
        $interaction = InteraccionCliente::create([
            'cliente_id' => $cliente->id,
            'registrado_por_user_id' => $director->id,
            'tipo' => 'correo',
            'descripcion' => 'Correo histórico',
        ]);

        $this->apiGet($this->interactionUrl($cliente), $director)->assertOk()->assertJsonCount(1, 'data');
        $this->apiGet($this->interactionUrl($cliente).'/'.$interaction->id, $director)->assertOk();
        $this->apiPost($this->interactionUrl($cliente), $director, [
            'tipo' => 'nota',
            'descripcion' => 'No permitido',
        ])->assertForbidden();
        $this->apiPatch($this->interactionUrl($cliente).'/'.$interaction->id, $director, [
            'descripcion' => 'No permitido',
        ])->assertForbidden();
        $this->apiDelete($this->interactionUrl($cliente).'/'.$interaction->id, $director)->assertForbidden();
    }

    public function test_client_has_no_interaction_access(): void
    {
        $clientUser = $this->user('portal-interactions@example.test', 'Cliente');
        $cliente = $this->client('Portal', $clientUser);

        $this->apiGet($this->interactionUrl($cliente), $clientUser)->assertForbidden();
        $this->apiPost($this->interactionUrl($cliente), $clientUser, [
            'tipo' => 'nota',
            'descripcion' => 'No permitido',
        ])->assertForbidden();
    }

    public function test_agent_can_read_create_update_only_for_assigned_client_and_cannot_delete(): void
    {
        $agent = $this->agentUser('agent-interactions@example.test');
        $assigned = $this->client('Asignado');
        $unassigned = $this->client('No asignado');
        ClienteAgente::create([
            'cliente_id' => $assigned->id,
            'agente_id' => $agent->agente->id,
            'es_principal' => true,
        ]);

        $created = $this->apiPost($this->interactionUrl($assigned), $agent, [
            'tipo' => 'whatsapp',
            'descripcion' => 'Mensaje al Cliente asignado',
        ])->assertCreated()
            ->assertJsonPath('data.registrado_por_user_id', $agent->id);
        $interaction = InteraccionCliente::query()->findOrFail($created->json('data.id'));

        $this->apiGet($this->interactionUrl($assigned), $agent)->assertOk()->assertJsonCount(1, 'data');
        $this->apiPatch($this->interactionUrl($assigned).'/'.$interaction->id, $agent, [
            'resultado' => 'Respondió',
        ])->assertOk();
        $this->apiDelete($this->interactionUrl($assigned).'/'.$interaction->id, $agent)->assertForbidden();
        $this->apiGet($this->interactionUrl($unassigned), $agent)->assertForbidden();
        $this->apiPost($this->interactionUrl($unassigned), $agent, [
            'tipo' => 'nota',
            'descripcion' => 'No permitido',
        ])->assertForbidden();
    }

    public function test_agent_without_profile_fails_closed(): void
    {
        $agent = $this->user('orphan-interactions@example.test', 'Agente Inmobiliario');
        $cliente = $this->client('Cliente huérfano');
        $interaction = InteraccionCliente::create([
            'cliente_id' => $cliente->id,
            'registrado_por_user_id' => $agent->id,
            'tipo' => 'nota',
            'descripcion' => 'Interacción existente',
        ]);

        $this->apiGet($this->interactionUrl($cliente), $agent)->assertForbidden();
        $this->apiGet($this->interactionUrl($cliente).'/'.$interaction->id, $agent)->assertForbidden();
        $this->apiPost($this->interactionUrl($cliente), $agent, [
            'tipo' => 'nota',
            'descripcion' => 'No permitido',
        ])->assertForbidden();
    }

    public function test_create_defaults_date_and_rejects_spoofed_author_and_internal_fields(): void
    {
        $admin = $this->user('validation-interactions@example.test', 'Administrador');
        $cliente = $this->client('Validación');

        $created = $this->apiPost($this->interactionUrl($cliente), $admin, [
            'tipo' => 'seguimiento',
            'descripcion' => 'Fecha por defecto',
        ])->assertCreated();
        $interaction = InteraccionCliente::query()->findOrFail($created->json('data.id'));
        self::assertNotNull($interaction->fecha_interaccion);

        foreach (['cliente_id', 'registrado_por_user_id', 'agente_id', 'id', 'created_at', 'updated_at', 'deleted_at', 'roles', 'permisos', 'cliente', 'registradoPor'] as $field) {
            $this->apiPost($this->interactionUrl($cliente), $admin, [
                'tipo' => 'nota',
                'descripcion' => 'Campo prohibido',
                $field => $field === 'id' ? 100 : ($field === 'cliente_id' ? $cliente->id : ['forbidden']),
            ])->assertUnprocessable();
        }
    }

    public function test_validation_covers_type_description_dates_and_optional_text(): void
    {
        $admin = $this->user('validation-rules-interactions@example.test', 'Administrador');
        $cliente = $this->client('Reglas');

        $this->apiPost($this->interactionUrl($cliente), $admin, [
            'descripcion' => 'Falta tipo',
        ])->assertUnprocessable();
        $this->apiPost($this->interactionUrl($cliente), $admin, [
            'tipo' => 'invalid',
            'descripcion' => 'Tipo inválido',
        ])->assertUnprocessable();
        $this->apiPost($this->interactionUrl($cliente), $admin, [
            'tipo' => 'nota',
            'descripcion' => 'Fecha inválida',
            'fecha_interaccion' => '2026-09-20',
        ])->assertUnprocessable();
        $this->apiPost($this->interactionUrl($cliente), $admin, [
            'tipo' => 'nota',
            'descripcion' => 'Resultado largo',
            'resultado' => str_repeat('x', 256),
        ])->assertUnprocessable();

        $this->apiPost($this->interactionUrl($cliente), $admin, [
            'tipo' => 'reunion',
            'descripcion' => 'Campos opcionales',
            'resultado' => null,
            'proxima_accion' => null,
            'fecha_proxima_accion' => null,
        ])->assertCreated();
    }

    public function test_update_is_whitelisted_and_preserves_client_author_and_historical_date(): void
    {
        $admin = $this->user('update-interactions@example.test', 'Administrador');
        $cliente = $this->client('Actualización');
        $interaction = InteraccionCliente::create([
            'cliente_id' => $cliente->id,
            'registrado_por_user_id' => $admin->id,
            'tipo' => 'llamada',
            'descripcion' => 'Original',
            'fecha_interaccion' => '2026-09-01 10:00:00',
        ]);

        $this->apiPatch($this->interactionUrl($cliente).'/'.$interaction->id, $admin, [
            'tipo' => 'correo',
            'descripcion' => 'Actualizada',
            'resultado' => 'Completada',
            'fecha_proxima_accion' => '2026-09-03 10:00:00',
        ])->assertOk();
        $fresh = $interaction->fresh();
        self::assertSame($cliente->id, $fresh->cliente_id);
        self::assertSame($admin->id, $fresh->registrado_por_user_id);
        self::assertSame('2026-09-01 10:00:00', $fresh->fecha_interaccion->format('Y-m-d H:i:s'));

        foreach (['cliente_id', 'registrado_por_user_id', 'fecha_interaccion', 'id', 'created_at', 'updated_at', 'deleted_at', 'agente_id', 'roles', 'permisos'] as $field) {
            $this->apiPatch($this->interactionUrl($cliente).'/'.$interaction->id, $admin, [
                $field => $field === 'fecha_interaccion' ? '2026-10-01 10:00:00' : ($field === 'id' ? 999 : ['forbidden']),
            ])->assertUnprocessable();
        }
    }

    public function test_scoped_binding_excludes_other_client_soft_deleted_client_and_soft_deleted_interaction(): void
    {
        $admin = $this->user('scope-interactions@example.test', 'Administrador');
        $clientA = $this->client('Cliente A');
        $clientB = $this->client('Cliente B');
        $interaction = InteraccionCliente::create([
            'cliente_id' => $clientB->id,
            'registrado_por_user_id' => $admin->id,
            'tipo' => 'nota',
            'descripcion' => 'Scoped',
        ]);

        $this->apiGet($this->interactionUrl($clientA).'/'.$interaction->id, $admin)->assertNotFound();
        $this->apiPatch($this->interactionUrl($clientA).'/'.$interaction->id, $admin, ['descripcion' => 'No'])->assertNotFound();
        $this->apiDelete($this->interactionUrl($clientA).'/'.$interaction->id, $admin)->assertNotFound();

        $interaction->delete();
        $this->apiGet($this->interactionUrl($clientB).'/'.$interaction->id, $admin)->assertNotFound();
        $this->apiGet($this->interactionUrl($clientB), $admin)->assertOk()->assertJsonCount(0, 'data');

        $clientB->delete();
        $this->apiGet($this->interactionUrl($clientB), $admin)->assertNotFound();
    }

    public function test_filters_are_applied_after_agent_scope_and_q_is_grouped(): void
    {
        $agent = $this->agentUser('filter-interactions-agent@example.test');
        $assigned = $this->client('Cliente asignado filtro');
        $other = $this->client('Cliente ajeno filtro');
        ClienteAgente::create(['cliente_id' => $assigned->id, 'agente_id' => $agent->agente->id, 'es_principal' => true]);
        $own = InteraccionCliente::create([
            'cliente_id' => $assigned->id,
            'registrado_por_user_id' => $agent->id,
            'tipo' => 'llamada',
            'descripcion' => 'Consulta visible',
            'resultado' => 'Pendiente',
            'fecha_interaccion' => '2026-09-01 10:00:00',
        ]);
        InteraccionCliente::create([
            'cliente_id' => $other->id,
            'registrado_por_user_id' => $agent->id,
            'tipo' => 'correo',
            'descripcion' => 'Consulta ajena',
            'resultado' => 'Pendiente',
        ]);

        $this->apiGet($this->interactionUrl($assigned).'?q=Consulta&tipo=llamada&fecha_interaccion_desde=2026-09-01 00:00:00', $agent)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
        $this->apiGet($this->interactionUrl($assigned).'?sort=invalid', $agent)->assertUnprocessable();
        $this->apiGet($this->interactionUrl($assigned).'?direction=invalid', $agent)->assertUnprocessable();
    }

    public function test_soft_deleted_registrador_does_not_invalidate_historical_interaction(): void
    {
        $admin = $this->user('deleted-registrar@example.test', 'Administrador');
        $reader = $this->user('historical-reader@example.test', 'Administrador');
        $cliente = $this->client('Histórico');
        $interaction = InteraccionCliente::create([
            'cliente_id' => $cliente->id,
            'registrado_por_user_id' => $admin->id,
            'tipo' => 'nota',
            'descripcion' => 'Registro histórico',
        ]);
        $admin->delete();

        $this->apiGet($this->interactionUrl($cliente), $reader)
            ->assertOk()
            ->assertJsonPath('data.0.registrado_por_user_id', $admin->id)
            ->assertJsonMissingPath('data.0.registrado_por.password');
        self::assertDatabaseHas('interacciones_cliente', [
            'id' => $interaction->id,
            'registrado_por_user_id' => $admin->id,
        ]);
    }

    private function user(string $email, ?string $role = null): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Interacciones',
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
            'email' => 'interaccion-client-'.$sequence.'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function interactionUrl(Cliente $cliente): string
    {
        return '/api/v1/clientes/'.$cliente->id.'/interacciones';
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
