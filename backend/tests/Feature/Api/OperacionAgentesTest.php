<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OperacionAgentesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_director_can_read_assignments_but_cannot_mutate_them(): void
    {
        $admin = $this->user('assignment-admin@example.test', 'Administrador');
        $director = $this->user('assignment-director@example.test', 'Director General');
        $agentUser = $this->user('assignment-agent@example.test', 'Agente Inmobiliario');
        $agent = Agente::create(['user_id' => $agentUser->id, 'numero_empleado' => 'ASSIGN-1', 'porcentaje_comision' => '2.00']);
        $client = Cliente::create(['nombres' => 'Cliente', 'apellido_paterno' => 'Asignación']);
        $property = $this->property('OP-ASSIGN');
        $opportunity = Oportunidad::create(['cliente_id' => $client->id, 'inmueble_id' => $property->id, 'titulo' => 'Asignación']);
        $operation = Operacion::create([
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'registrado_por_user_id' => $admin->id,
            'tipo_operacion' => 'venta',
            'monto' => '1000.00',
        ]);

        $created = $this->apiPost('/api/v1/operaciones/'.$operation->id.'/agentes', $admin, ['agente_id' => $agent->id])
            ->assertCreated();
        $this->apiGet('/api/v1/operaciones/'.$operation->id.'/agentes', $director)
            ->assertOk()->assertJsonPath('data.0.agente_id', $agent->id);
        $this->apiPatch('/api/v1/operaciones/'.$operation->id.'/agentes/'.$created->json('data.id').'/principal', $director, [])
            ->assertForbidden();
        $this->apiDelete('/api/v1/operaciones/'.$operation->id.'/agentes/'.$created->json('data.id'), $director)
            ->assertForbidden();
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Asignación',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function property(string $code): Inmueble
    {
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario '.$code,
            'rfc' => 'RFC'.str_pad($code, 10, '0'),
            'telefono' => '5555555555',
            'direccion' => 'Dirección '.$code,
        ]);
        $category = Categoria::create(['nombre' => 'Categoría '.$code]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => $code,
            'titulo' => 'Inmueble '.$code,
            'slug' => strtolower($code),
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

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->getJson($uri);
    }

    private function apiPost(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->postJson($uri, $payload);
    }

    private function apiPatch(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->patchJson($uri, $payload);
    }

    private function apiDelete(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->deleteJson($uri);
    }
}
