<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\Categoria;
use App\Models\CategoriaDocumento;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\OperacionAgente;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OperacionesTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_private_operations_require_authentication(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/operaciones')
            ->assertUnauthorized();
    }

    public function test_administrator_can_create_update_list_show_and_manage_operation_agents(): void
    {
        $admin = $this->user('admin-operations@example.test', 'Administrador');
        $client = $this->client('Cliente operación');
        $property = $this->property('OP-ADMIN', 'venta');
        $opportunity = $this->opportunity($client, $property, 'Cierre administrativo');
        $agent = $this->agentUser('operation-agent@example.test', '3.50');

        $created = $this->apiPost('/api/v1/operaciones', $admin, [
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'tipo_operacion' => 'venta',
            'monto' => '100000.00',
            'fecha_operacion' => '2026-01-15 10:30:00',
            'observaciones' => 'Cierre histórico',
        ])->assertCreated()
            ->assertJsonPath('data.estado', 'registrada')
            ->assertJsonPath('data.registrado_por_user_id', $admin->id)
            ->assertJsonPath('data.monto', '100000.00');

        $operation = Operacion::query()->findOrFail($created->json('data.id'));
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'operacion_creada',
            'entidad' => 'operacion',
            'entidad_id' => $operation->id,
            'user_id' => $admin->id,
        ]);
        self::assertSame('2026-01-15 10:30:00', $operation->fecha_operacion->format('Y-m-d H:i:s'));

        $assignment = $this->apiPost('/api/v1/operaciones/'.$operation->id.'/agentes', $admin, [
            'agente_id' => $agent->agente->id,
            'es_principal' => false,
        ])->assertCreated()
            ->assertJsonPath('data.es_principal', true)
            ->assertJsonPath('data.porcentaje_comision', '3.50')
            ->assertJsonPath('data.monto_comision', '3500.00');
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'operacion_agente_asignado',
            'entidad' => 'operacion_agente',
            'entidad_id' => $assignment->json('data.id'),
            'user_id' => $admin->id,
        ]);

        $this->apiGet('/api/v1/operaciones', $admin)->assertOk()->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/operaciones/'.$operation->id, $admin)
            ->assertOk()
            ->assertJsonPath('data.agentes.0.agente_id', $agent->agente->id)
            ->assertJsonMissingPath('data.agentes.0.agente.porcentaje_comision');

        $this->apiPatch('/api/v1/operaciones/'.$operation->id, $admin, [
            'estado' => 'anulada',
            'fecha_inicio_contrato' => '2026-01-16',
            'fecha_fin_contrato' => '2027-01-15',
            'observaciones' => 'Anulada administrativamente',
        ])->assertOk()->assertJsonPath('data.estado', 'anulada');
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'operacion_actualizada',
            'entidad_id' => $operation->id,
            'user_id' => $admin->id,
        ]);

        $assignmentModel = OperacionAgente::query()->findOrFail($assignment->json('data.id'));
        $this->apiDelete('/api/v1/operaciones/'.$operation->id.'/agentes/'.$assignmentModel->id, $admin)
            ->assertNoContent();
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'operacion_agente_desasignado',
            'entidad_id' => $assignmentModel->id,
            'user_id' => $admin->id,
        ]);
        $this->apiDelete('/api/v1/operaciones/'.$operation->id, $admin)->assertNoContent();
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'operacion_eliminada',
            'entidad_id' => $operation->id,
            'user_id' => $admin->id,
        ]);
        self::assertDatabaseMissing('operaciones', ['id' => $operation->id]);
        self::assertDatabaseHas('clientes', ['id' => $client->id]);
        self::assertDatabaseHas('inmuebles', ['id' => $property->id]);
        self::assertDatabaseHas('oportunidades', ['id' => $opportunity->id]);
    }

    public function test_assistant_can_manage_but_cannot_delete_and_director_is_read_only(): void
    {
        $assistant = $this->user('assistant-operations@example.test', 'Asistente');
        $director = $this->user('director-operations@example.test', 'Director General');
        $client = $this->client('Cliente roles operación');
        $property = $this->property('OP-ROLES', 'venta');
        $opportunity = $this->opportunity($client, $property, 'Oportunidad roles');
        $operation = $this->operation($assistant, $opportunity, $client, $property);

        $this->apiGet('/api/v1/operaciones/'.$operation->id, $director)->assertOk();
        $this->apiPatch('/api/v1/operaciones/'.$operation->id, $assistant, ['observaciones' => 'Asistente'])
            ->assertOk();
        $this->apiDelete('/api/v1/operaciones/'.$operation->id, $assistant)->assertForbidden();
        $this->apiPatch('/api/v1/operaciones/'.$operation->id, $director, ['observaciones' => 'No'])
            ->assertForbidden();
        $this->apiPost('/api/v1/operaciones', $director, [
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'tipo_operacion' => 'venta',
            'monto' => '100.00',
        ])->assertForbidden();
    }

    public function test_agent_only_reads_operations_where_directly_assigned(): void
    {
        $admin = $this->user('scope-operation-admin@example.test', 'Administrador');
        $agentUser = $this->agentUser('scope-operation-agent@example.test', '2.00');
        $client = $this->client('Cliente visible operación');
        $property = $this->property('OP-SCOPE', 'venta');
        $opportunity = $this->opportunity($client, $property, 'Oportunidad visible');
        $operation = $this->operation($admin, $opportunity, $client, $property);
        $assignment = OperacionAgente::create([
            'operacion_id' => $operation->id,
            'agente_id' => $agentUser->agente->id,
            'es_principal' => true,
            'porcentaje_comision' => '2.00',
            'monto_comision' => '2000.00',
        ]);

        $this->apiGet('/api/v1/operaciones', $agentUser)->assertOk()->assertJsonFragment(['id' => $operation->id]);
        $this->apiGet('/api/v1/operaciones/'.$operation->id.'/agentes', $agentUser)
            ->assertOk()->assertJsonPath('data.0.id', $assignment->id);

        $otherClient = $this->client('Cliente no visible');
        $otherProperty = $this->property('OP-OTHER', 'venta');
        $otherOperation = $this->operation($admin, $this->opportunity($otherClient, $otherProperty, 'Oportunidad ajena'), $otherClient, $otherProperty);
        $this->apiGet('/api/v1/operaciones/'.$otherOperation->id, $agentUser)->assertForbidden();
        $this->apiPost('/api/v1/operaciones', $agentUser, [
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'tipo_operacion' => 'venta',
            'monto' => '100.00',
        ])->assertForbidden();
    }

    public function test_create_validates_coherence_type_unique_and_internal_fields(): void
    {
        $admin = $this->user('validation-operation-admin@example.test', 'Administrador');
        $client = $this->client('Cliente validación operación');
        $property = $this->property('OP-VALID', 'venta');
        $opportunity = $this->opportunity($client, $property, 'Oportunidad validación');
        $payload = [
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'tipo_operacion' => 'venta',
            'monto' => '10.00',
        ];

        $this->apiPost('/api/v1/operaciones', $admin, array_merge($payload, [
            'estado' => 'anulada',
            'registrado_por_user_id' => 999,
        ]))->assertUnprocessable();
        $this->apiPost('/api/v1/operaciones', $admin, $payload)->assertCreated();
        $this->apiPost('/api/v1/operaciones', $admin, $payload)->assertUnprocessable();

        $rentalProperty = $this->property('OP-RENT', 'renta');
        $this->apiPost('/api/v1/operaciones', $admin, [
            'oportunidad_id' => $this->opportunity($client, $rentalProperty, 'Oportunidad tipo')->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $rentalProperty->id,
            'tipo_operacion' => 'venta',
            'monto' => '100.00',
        ])->assertUnprocessable();
    }

    public function test_commission_snapshots_use_decimal_half_up_and_remain_immutable(): void
    {
        $admin = $this->user('commission-operation-admin@example.test', 'Administrador');
        $client = $this->client('Cliente comisión');

        $cases = [
            ['code' => 'OP-COM-1', 'monto' => '1000.00', 'percentage' => '2.50', 'expected' => '25.00'],
            ['code' => 'OP-COM-2', 'monto' => '999.99', 'percentage' => '2.75', 'expected' => '27.50'],
            ['code' => 'OP-COM-3', 'monto' => '100.00', 'percentage' => '0.00', 'expected' => '0.00'],
            ['code' => 'OP-COM-4', 'monto' => '100.00', 'percentage' => '100.00', 'expected' => '100.00'],
        ];

        foreach ($cases as $index => $case) {
            $agentUser = $this->agentUser('commission-'.$index.'@example.test', $case['percentage']);
            $clientForCase = $index === 0 ? $client : $this->client('Cliente comisión '.$index);
            $property = $this->property($case['code'], 'venta');
            $operation = $this->operation($admin, $this->opportunity($clientForCase, $property, 'Comisión '.$index), $clientForCase, $property, $case['monto']);

            $response = $this->apiPost('/api/v1/operaciones/'.$operation->id.'/agentes', $admin, [
                'agente_id' => $agentUser->agente->id,
            ])->assertCreated();
            $response->assertJsonPath('data.porcentaje_comision', $case['percentage']);
            $response->assertJsonPath('data.monto_comision', $case['expected']);

            $agentUser->agente->update(['porcentaje_comision' => '99.99']);
            self::assertSame($case['percentage'], OperacionAgente::findOrFail($response->json('data.id'))->porcentaje_comision);
        }
    }

    public function test_assignment_principal_rules_duplicate_inactive_and_scoped_binding(): void
    {
        $admin = $this->user('principal-operation-admin@example.test', 'Administrador');
        $client = $this->client('Cliente principales');
        $property = $this->property('OP-PRINCIPAL', 'venta');
        $operation = $this->operation($admin, $this->opportunity($client, $property, 'Oportunidad principales'), $client, $property);
        $first = $this->agentUser('principal-first@example.test', '1.00');
        $second = $this->agentUser('principal-second@example.test', '1.00');
        $inactive = $this->agentUser('principal-inactive@example.test', '0.00');
        $inactive->agente->update(['estado_laboral' => 'inactivo']);

        $firstResponse = $this->apiPost('/api/v1/operaciones/'.$operation->id.'/agentes', $admin, [
            'agente_id' => $first->agente->id,
            'es_principal' => false,
        ])->assertCreated()->assertJsonPath('data.es_principal', true);
        $secondResponse = $this->apiPost('/api/v1/operaciones/'.$operation->id.'/agentes', $admin, [
            'agente_id' => $second->agente->id,
        ])->assertCreated()->assertJsonPath('data.es_principal', false);
        $this->apiPost('/api/v1/operaciones/'.$operation->id.'/agentes', $admin, [
            'agente_id' => $inactive->agente->id,
        ])->assertCreated();

        $secondAssignment = OperacionAgente::findOrFail($secondResponse->json('data.id'));
        $this->apiPatch('/api/v1/operaciones/'.$operation->id.'/agentes/'.$secondAssignment->id.'/principal', $admin, [])
            ->assertOk()->assertJsonPath('data.es_principal', true);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'operacion_agente_principal_cambiado',
            'entidad' => 'operacion_agente',
            'entidad_id' => $secondAssignment->id,
            'user_id' => $admin->id,
        ]);
        $this->apiPost('/api/v1/operaciones/'.$operation->id.'/agentes', $admin, [
            'agente_id' => $second->agente->id,
        ])->assertUnprocessable();

        $otherOperation = $this->operation($admin, $this->opportunity($client, $this->property('OP-OTHER-P', 'venta'), 'Otra'), $client, $property);
        $firstAssignment = OperacionAgente::findOrFail($firstResponse->json('data.id'));
        $this->apiPatch('/api/v1/operaciones/'.$otherOperation->id.'/agentes/'.$firstAssignment->id.'/principal', $admin, [])
            ->assertNotFound();
    }

    public function test_delete_operation_is_blocked_by_assignments_or_documents(): void
    {
        $admin = $this->user('delete-operation-admin@example.test', 'Administrador');
        $client = $this->client('Cliente eliminar operación');
        $property = $this->property('OP-DELETE', 'venta');
        $operation = $this->operation($admin, $this->opportunity($client, $property, 'Eliminar operación'), $client, $property);
        $agent = $this->agentUser('delete-operation-agent@example.test', '1.00');

        $this->apiPost('/api/v1/operaciones/'.$operation->id.'/agentes', $admin, ['agente_id' => $agent->agente->id])->assertCreated();
        $this->apiDelete('/api/v1/operaciones/'.$operation->id, $admin)->assertUnprocessable();

        OperacionAgente::query()->delete();
        $category = CategoriaDocumento::create(['nombre' => 'Contrato operación']);
        Documento::create([
            'categoria_documento_id' => $category->id,
            'subido_por_user_id' => $admin->id,
            'operacion_id' => $operation->id,
            'nombre_original' => 'contrato.pdf',
            'firebase_path' => 'documents/operation.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $this->apiDelete('/api/v1/operaciones/'.$operation->id, $admin)->assertUnprocessable();
    }

    public function test_index_filters_are_applied_after_operation_visibility(): void
    {
        $admin = $this->user('filter-operation-admin@example.test', 'Administrador');
        $agentUser = $this->agentUser('filter-operation-agent@example.test', '1.00');
        $client = $this->client('Cliente filtro operación');
        $property = $this->property('OP-FILTER', 'venta');
        $operation = $this->operation($admin, $this->opportunity($client, $property, 'Filtro'), $client, $property, '2500.00');
        $operation->update(['observaciones' => 'Filtro comercial']);
        OperacionAgente::create([
            'operacion_id' => $operation->id,
            'agente_id' => $agentUser->agente->id,
            'es_principal' => true,
            'porcentaje_comision' => '1.00',
            'monto_comision' => '25.00',
        ]);

        $this->apiGet('/api/v1/operaciones?agente_id='.$agentUser->agente->id.'&monto_min=2000&q=Filtro', $agentUser)
            ->assertOk()->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/operaciones?agente_id=999999', $agentUser)->assertUnprocessable();
    }

    protected function operation(User $creator, Oportunidad $opportunity, Cliente $client, Inmueble $property, string $amount = '100000.00'): Operacion
    {
        return Operacion::create([
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'registrado_por_user_id' => $creator->id,
            'tipo_operacion' => 'venta',
            'monto' => $amount,
            'estado' => 'registrada',
        ]);
    }

    protected function opportunity(Cliente $client, Inmueble $property, string $title): Oportunidad
    {
        return Oportunidad::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'titulo' => $title,
        ]);
    }

    protected function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Operaciones',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    protected function agentUser(string $email, string $percentage): User
    {
        $user = $this->user($email, 'Agente Inmobiliario');
        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => 'OP-AG-'.(++self::$sequence),
            'porcentaje_comision' => $percentage,
            'estado_laboral' => 'activo',
        ]);

        return $user->fresh('agente');
    }

    protected function client(string $name): Cliente
    {
        return Cliente::create([
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => 'op-client-'.(++self::$sequence).'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    protected function property(string $code, string $type): Inmueble
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
            'tipo_operacion' => $type,
            'precio_venta' => $type === 'venta' ? 100000 : null,
            'renta_mensual' => $type === 'renta' ? 10000 : null,
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
