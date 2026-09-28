<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\Categoria;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\HistorialCorreo;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HistorialCorreosTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_private_history_requires_authentication(): void
    {
        $correo = HistorialCorreo::create($this->emailPayload());

        $this->getJson('/api/v1/historial-correos')->assertUnauthorized();
        $this->getJson('/api/v1/historial-correos/'.$correo->id)->assertUnauthorized();
    }

    public function test_administrator_has_global_index_show_and_error_visibility(): void
    {
        $admin = $this->user('admin-history@example.test', 'Administrador');
        $correo = HistorialCorreo::create($this->emailPayload([
            'estado' => 'fallido',
            'mensaje_error' => 'Proveedor temporalmente no disponible',
            'proveedor_message_id' => 'provider-secret-id',
        ]));

        $this->apiGet('/api/v1/historial-correos', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.id', $correo->id)
            ->assertJsonMissingPath('data.0.mensaje_error')
            ->assertJsonMissingPath('data.0.proveedor_message_id');

        $this->apiGet('/api/v1/historial-correos/'.$correo->id, $admin)
            ->assertOk()
            ->assertJsonPath('data.mensaje_error', 'Proveedor temporalmente no disponible')
            ->assertJsonMissingPath('data.proveedor_message_id');
    }

    public function test_agent_visibility_uses_all_approved_independent_paths(): void
    {
        $agent = $this->agentUser('history-agent@example.test');
        $otherAgent = $this->agentUser('history-other@example.test');
        $assignedClient = $this->client('Cliente asignado');
        $unassignedClient = $this->client('Cliente no asignado');
        ClienteAgente::create([
            'cliente_id' => $assignedClient->id,
            'agente_id' => $agent->agente->id,
        ]);
        $property = $this->property('HIST-001');
        $appointment = Cita::create([
            'cliente_id' => $unassignedClient->id,
            'agente_id' => $agent->agente->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $agent->id,
            'fecha_inicio' => '2026-10-10 10:00:00',
            'fecha_fin' => '2026-10-10 11:00:00',
        ]);
        $byClient = HistorialCorreo::create($this->emailPayload([
            'cliente_id' => $assignedClient->id,
            'asunto' => 'Cliente asignado',
        ]));
        $byAppointment = HistorialCorreo::create($this->emailPayload([
            'cita_id' => $appointment->id,
            'asunto' => 'Cita del agente',
        ]));
        $bySender = HistorialCorreo::create($this->emailPayload([
            'enviado_por_user_id' => $agent->id,
            'asunto' => 'Enviado por agente',
        ]));
        $byRecipient = HistorialCorreo::create($this->emailPayload([
            'destinatario_user_id' => $agent->id,
            'asunto' => 'Dirigido al agente',
        ]));
        $unrelated = HistorialCorreo::create($this->emailPayload([
            'cliente_id' => $unassignedClient->id,
            'asunto' => 'No relacionado',
        ]));

        $this->apiGet('/api/v1/historial-correos', $agent)
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['id' => $byClient->id])
            ->assertJsonFragment(['id' => $byAppointment->id])
            ->assertJsonFragment(['id' => $bySender->id])
            ->assertJsonFragment(['id' => $byRecipient->id])
            ->assertJsonMissing(['id' => $unrelated->id]);

        $this->apiGet('/api/v1/historial-correos/'.$byAppointment->id, $agent)->assertOk();
        $this->apiGet('/api/v1/historial-correos/'.$unrelated->id, $agent)->assertForbidden();
        $this->apiGet('/api/v1/historial-correos/'.$byClient->id, $otherAgent)->assertForbidden();
    }

    public function test_agent_cannot_see_error_or_provider_identifier(): void
    {
        $agent = $this->agentUser('history-safe-agent@example.test');
        $client = $this->client('Cliente histórico');
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        $correo = HistorialCorreo::create($this->emailPayload([
            'cliente_id' => $client->id,
            'mensaje_error' => 'Detalle interno del proveedor',
            'proveedor_message_id' => 'internal-provider-id',
        ]));

        $this->apiGet('/api/v1/historial-correos/'.$correo->id, $agent)
            ->assertOk()
            ->assertJsonMissingPath('data.mensaje_error')
            ->assertJsonMissingPath('data.proveedor_message_id');
    }

    public function test_roles_without_permission_are_rejected(): void
    {
        $assistant = $this->user('assistant-history@example.test', 'Asistente');
        $director = $this->user('director-history@example.test', 'Director General');
        $clientUser = $this->user('client-history@example.test', 'Cliente');
        $correo = HistorialCorreo::create($this->emailPayload());

        foreach ([$assistant, $director, $clientUser] as $user) {
            $this->apiGet('/api/v1/historial-correos', $user)->assertForbidden();
            $this->apiGet('/api/v1/historial-correos/'.$correo->id, $user)->assertForbidden();
        }
    }

    public function test_agent_without_profile_fails_closed(): void
    {
        $agent = $this->user('orphan-history@example.test', 'Agente Inmobiliario');
        $correo = HistorialCorreo::create($this->emailPayload());

        $this->apiGet('/api/v1/historial-correos', $agent)->assertForbidden();
        $this->apiGet('/api/v1/historial-correos/'.$correo->id, $agent)->assertForbidden();
    }

    public function test_index_filters_sort_pagination_and_grouped_search(): void
    {
        $admin = $this->user('filters-history@example.test', 'Administrador');
        HistorialCorreo::create($this->emailPayload([
            'destinatario_email' => 'visible@example.test',
            'destinatario_nombre' => 'Cliente Visible',
            'asunto' => 'Seguimiento de cita',
            'tipo' => 'confirmacion_cita',
            'estado' => 'enviado',
            'fecha_envio' => '2026-09-10 10:00:00',
        ]));
        HistorialCorreo::create($this->emailPayload([
            'destinatario_email' => 'other@example.test',
            'asunto' => 'Seguridad',
            'tipo' => 'seguridad',
            'estado' => 'fallido',
            'fecha_envio' => null,
        ]));

        $base = '/api/v1/historial-correos';
        $this->apiGet($base.'?estado=enviado&tipo=confirmacion_cita&destinatario_email=visible@example.test', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet($base.'?fecha_envio_desde=2026-09-01 00:00:00&fecha_envio_hasta=2026-09-30 23:59:59', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet($base.'?q=Cliente Visible', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet($base.'?per_page=1&sort=asunto&direction=asc', $admin)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);
        $this->apiGet($base.'?sort=invalid', $admin)->assertUnprocessable();
        $this->apiGet($base.'?direction=invalid', $admin)->assertUnprocessable();
    }

    public function test_show_binding_and_soft_deleted_related_user_preserve_history(): void
    {
        $admin = $this->user('binding-history@example.test', 'Administrador');
        $sender = $this->user('deleted-history-sender@example.test', 'Agente Inmobiliario');
        $correo = HistorialCorreo::create($this->emailPayload([
            'enviado_por_user_id' => $sender->id,
        ]));
        $sender->delete();

        $this->apiGet('/api/v1/historial-correos/'.$correo->id, $admin)
            ->assertOk()
            ->assertJsonPath('data.enviado_por_user_id', $sender->id);
        $this->apiGet('/api/v1/historial-correos/999999', $admin)->assertNotFound();
    }

    public function test_api_is_append_only_without_write_routes(): void
    {
        $admin = $this->user('append-only-history@example.test', 'Administrador');
        $correo = HistorialCorreo::create($this->emailPayload());
        $url = '/api/v1/historial-correos';

        $this->actingAs($admin, 'web')->postJson($url, $this->emailPayload())->assertMethodNotAllowed();
        $this->apiPatch($url.'/'.$correo->id, $admin, ['asunto' => 'No'])->assertMethodNotAllowed();
        $this->apiDelete($url.'/'.$correo->id, $admin)->assertMethodNotAllowed();
        self::assertDatabaseHas('historial_correos', ['id' => $correo->id, 'asunto' => 'Asunto histórico']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function emailPayload(array $overrides = []): array
    {
        return array_merge([
            'destinatario_email' => 'recipient@example.test',
            'destinatario_nombre' => 'Destinatario',
            'tipo' => 'otro',
            'asunto' => 'Asunto histórico',
            'plantilla' => 'general',
            'estado' => 'pendiente',
        ], $overrides);
    }

    private function user(string $email, ?string $role = null): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Correo',
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

        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => 'HIS-'.(++self::$sequence),
            'estado_laboral' => 'activo',
        ]);

        return $user->fresh('agente');
    }

    private function client(string $name): Cliente
    {
        return Cliente::create([
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => 'history-client-'.(++self::$sequence).'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function property(string $code): Inmueble
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

    private function apiGet(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
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
