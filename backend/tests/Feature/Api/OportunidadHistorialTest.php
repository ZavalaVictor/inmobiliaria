<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Oportunidad;
use App\Models\OportunidadHistorial;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OportunidadHistorialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_history_is_read_only_and_uses_opportunity_visibility(): void
    {
        $admin = $this->user('admin-history@example.test', 'Administrador');
        $director = $this->user('director-history@example.test', 'Director General');
        $client = Cliente::create([
            'nombres' => 'Cliente historial',
            'apellido_paterno' => 'Prueba',
            'email' => 'client-history@example.test',
        ]);
        $opportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'titulo' => 'Historial de oportunidad',
        ]);
        OportunidadHistorial::create([
            'oportunidad_id' => $opportunity->id,
            'cambiado_por_user_id' => $admin->id,
            'tipo_evento' => 'creacion',
            'etapa_nueva' => 'contacto_inicial',
            'estado_nuevo' => 'activa',
        ]);

        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id.'/historial', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.cambiado_por.id', $admin->id)
            ->assertJsonMissingPath('data.0.cambiado_por.password')
            ->assertJsonMissingPath('data.0.updated_at');
        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id.'/historial', $director)
            ->assertOk();
        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id.'/historial?per_page=101', $admin)
            ->assertUnprocessable();
        $this->apiPost('/api/v1/oportunidades/'.$opportunity->id.'/historial', $admin, [])
            ->assertMethodNotAllowed();
        $this->apiDelete('/api/v1/oportunidades/'.$opportunity->id.'/historial', $admin)
            ->assertMethodNotAllowed();
    }

    public function test_soft_deleted_opportunity_history_is_not_resolvable(): void
    {
        $admin = $this->user('admin-history-deleted@example.test', 'Administrador');
        $client = Cliente::create([
            'nombres' => 'Cliente eliminado historial',
            'apellido_paterno' => 'Prueba',
            'email' => 'deleted-history-client@example.test',
        ]);
        $opportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'titulo' => 'Historial eliminado',
        ]);
        OportunidadHistorial::create([
            'oportunidad_id' => $opportunity->id,
            'cambiado_por_user_id' => $admin->id,
            'tipo_evento' => 'creacion',
            'etapa_nueva' => 'contacto_inicial',
            'estado_nuevo' => 'activa',
        ]);
        $opportunity->delete();

        $this->apiGet('/api/v1/oportunidades/'.$opportunity->id.'/historial', $admin)
            ->assertNotFound();
        self::assertDatabaseHas('oportunidad_historial', ['oportunidad_id' => $opportunity->id]);
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

    private function apiDelete(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson($uri);
    }
}
