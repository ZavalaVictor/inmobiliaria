<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cita;
use App\Models\CitaHistorial;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CitaHistorialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_history_endpoint_is_read_only_and_returns_safe_relations(): void
    {
        $admin = $this->user('history-readonly@example.test', 'Administrador');
        $agentUser = $this->user('history-agent@example.test', 'Agente Inmobiliario');
        $agent = Agente::create(['user_id' => $agentUser->id, 'numero_empleado' => 'HIST-1']);
        $client = Cliente::create(['nombres' => 'Historial', 'apellido_paterno' => 'Cliente']);
        $property = $this->property('HIST-1');
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->id]);
        AgenteInmueble::create(['inmueble_id' => $property->id, 'agente_id' => $agent->id]);
        $cita = Cita::create([
            'cliente_id' => $client->id,
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'creado_por_user_id' => $admin->id,
            'fecha_inicio' => '2026-10-01 19:00:00',
            'fecha_fin' => '2026-10-01 20:00:00',
        ]);
        CitaHistorial::create([
            'cita_id' => $cita->id,
            'modificado_por_user_id' => $admin->id,
            'agente_anterior_id' => $agent->id,
            'agente_nuevo_id' => $agent->id,
            'fecha_inicio_anterior' => '2026-10-01 19:00:00',
            'fecha_fin_anterior' => '2026-10-01 20:00:00',
            'fecha_inicio_nueva' => '2026-10-01 21:00:00',
            'fecha_fin_nueva' => '2026-10-01 22:00:00',
            'motivo' => 'Cambio',
            'tipo_cambio' => 'reprogramacion',
        ]);

        $this->apiGet('/api/v1/citas/'.$cita->id.'/historial', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.updated_at')
            ->assertJsonMissingPath('data.0.modificado_por.password');
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Historial',
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
            'rfc' => 'RFC'.str_pad('1234567890', 10, '0'),
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

        return $this->actingAs($user, 'web')->withHeaders(['Accept' => 'application/json'])->getJson($uri);
    }
}
