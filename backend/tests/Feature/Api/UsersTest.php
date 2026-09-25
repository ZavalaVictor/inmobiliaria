<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
    }

    public function test_administrator_can_create_show_update_and_delete_user(): void
    {
        $admin = $this->user('admin-users@example.test', 'Administrador');

        $response = $this->apiPost('/api/v1/users', $admin, [
            'nombres' => 'Nuevo',
            'apellido_paterno' => 'Usuario',
            'email' => 'new-user@example.test',
            'telefono' => '5555555555',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated()
            ->assertJsonPath('data.nombres', 'Nuevo')
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');

        $user = User::query()->findOrFail($response->json('data.id'));
        self::assertTrue(Hash::check('Password123', $user->getRawOriginal('password')));
        self::assertDatabaseHas('users', [
            'id' => $user->id,
            'estado' => 'pendiente',
        ]);

        $this->apiGet('/api/v1/users/'.$user->id, $admin)->assertOk();
        $this->apiPatch('/api/v1/users/'.$user->id, $admin, [
            'nombres' => 'Actualizado',
            'estado' => 'activo',
        ])->assertOk()
            ->assertJsonPath('data.nombres', 'Actualizado')
            ->assertJsonPath('data.estado', 'activo');

        $this->apiDelete('/api/v1/users/'.$user->id, $admin)->assertNoContent();
        self::assertSoftDeleted('users', ['id' => $user->id]);
        $this->apiGet('/api/v1/users/'.$user->id, $admin)->assertNotFound();
    }

    public function test_non_administrator_roles_cannot_access_user_administration(): void
    {
        foreach (['Agente Inmobiliario', 'Asistente', 'Director General', 'Cliente'] as $role) {
            $user = $this->user(strtolower(str_replace(' ', '-', $role)).'-users@example.test', $role);

            $this->apiGet('/api/v1/users', $user)->assertForbidden();
        }
    }

    public function test_administrator_cannot_modify_delete_or_sync_own_account(): void
    {
        $admin = $this->user('admin-self-users@example.test', 'Administrador');

        $this->apiPatch('/api/v1/users/'.$admin->id, $admin, [
            'estado' => 'inactivo',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/users/'.$admin->id, $admin)->assertForbidden();
        $this->apiPut('/api/v1/users/'.$admin->id.'/roles', $admin, [
            'roles' => ['Asistente'],
        ])->assertForbidden();
    }

    public function test_create_does_not_create_roles_or_client_and_agent_profiles(): void
    {
        $admin = $this->user('admin-user-profiles@example.test', 'Administrador');

        $response = $this->apiPost('/api/v1/users', $admin, [
            'nombres' => 'Perfil',
            'apellido_paterno' => 'Pendiente',
            'email' => 'profile-pending@example.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated();

        $userId = $response->json('data.id');
        self::assertDatabaseMissing('model_has_roles', [
            'model_id' => $userId,
            'model_type' => User::class,
        ]);
        self::assertDatabaseMissing('clientes', ['user_id' => $userId]);
        self::assertDatabaseMissing('agentes', ['user_id' => $userId]);
    }

    public function test_password_confirmation_and_password_validation_are_enforced(): void
    {
        $admin = $this->user('admin-user-password@example.test', 'Administrador');

        $this->apiPost('/api/v1/users', $admin, [
            'nombres' => 'Password',
            'apellido_paterno' => 'Incorrecto',
            'email' => 'password-invalid@example.test',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable();

        $user = $this->user('password-update@example.test', 'Cliente');
        $this->apiPatch('/api/v1/users/'.$user->id, $admin, [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertUnprocessable();
    }

    public function test_email_is_unique_including_soft_deleted_users(): void
    {
        $admin = $this->user('admin-user-email@example.test', 'Administrador');
        $first = $this->user('email-reserved@example.test');
        $second = $this->user('email-second@example.test');

        $this->apiPost('/api/v1/users', $admin, [
            'nombres' => 'Duplicado',
            'apellido_paterno' => 'Email',
            'email' => $first->email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertUnprocessable();

        $this->apiPatch('/api/v1/users/'.$second->id, $admin, [
            'email' => $first->email,
        ])->assertUnprocessable();

        $first->delete();

        $this->apiPost('/api/v1/users', $admin, [
            'nombres' => 'Soft',
            'apellido_paterno' => 'Eliminado',
            'email' => $first->email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertUnprocessable();
    }

    public function test_update_can_keep_own_email_and_prohibited_fields_are_rejected(): void
    {
        $admin = $this->user('admin-user-update@example.test', 'Administrador');
        $user = $this->user('user-update@example.test', 'Cliente');

        $this->apiPatch('/api/v1/users/'.$user->id, $admin, [
            'email' => $user->email,
            'telefono' => '5550000000',
        ])->assertOk();

        foreach ([
            'id',
            'password',
            'password_confirmation',
            'remember_token',
            'email_verified_at',
            'last_login_at',
            'roles',
            'role',
            'permissions',
            'permisos',
            'cliente',
            'agente',
            'cliente_id',
            'agente_id',
            'created_at',
            'updated_at',
            'deleted_at',
        ] as $field) {
            $this->apiPatch('/api/v1/users/'.$user->id, $admin, [
                $field => 'forbidden',
            ])->assertUnprocessable();
        }
    }

    public function test_states_can_be_set_and_invalid_state_is_rejected(): void
    {
        $admin = $this->user('admin-user-states@example.test', 'Administrador');
        $user = $this->user('user-states@example.test', 'Cliente');

        foreach (['pendiente', 'activo', 'bloqueado', 'inactivo'] as $state) {
            $this->apiPatch('/api/v1/users/'.$user->id, $admin, [
                'estado' => $state,
            ])->assertOk()->assertJsonPath('data.estado', $state);
        }

        $this->apiPatch('/api/v1/users/'.$user->id, $admin, [
            'estado' => 'desconocido',
        ])->assertUnprocessable();
    }

    public function test_roles_endpoint_assigns_multiple_roles_and_replaces_previous_set(): void
    {
        $admin = $this->user('admin-user-roles@example.test', 'Administrador');
        $user = $this->user('user-roles@example.test', 'Cliente');

        $this->apiPut('/api/v1/users/'.$user->id.'/roles', $admin, [
            'roles' => ['Agente Inmobiliario', 'Asistente'],
        ])->assertOk()
            ->assertJsonPath('data.roles.0', 'Agente Inmobiliario')
            ->assertJsonPath('data.roles.1', 'Asistente');

        $this->apiPut('/api/v1/users/'.$user->id.'/roles', $admin, [
            'roles' => ['Director General'],
        ])->assertOk();

        self::assertEquals(['Director General'], $user->fresh()->getRoleNames()->all());
        self::assertDatabaseMissing('clientes', ['user_id' => $user->id]);
        self::assertDatabaseMissing('agentes', ['user_id' => $user->id]);
    }

    public function test_roles_endpoint_rejects_invalid_duplicate_empty_public_and_internal_payloads(): void
    {
        $admin = $this->user('admin-user-role-validation@example.test', 'Administrador');
        $user = $this->user('user-role-validation@example.test');

        $invalidPayloads = [
            ['roles' => ['Público General']],
            ['roles' => ['Rol Inventado']],
            ['roles' => ['Cliente', 'Cliente']],
            ['roles' => []],
            ['roles' => ['Cliente'], 'permissions' => ['usuarios.crear']],
            ['roles' => ['Cliente'], 'permisos' => ['usuarios.crear']],
            ['roles' => ['Cliente'], 'guard_name' => 'sanctum'],
            ['roles' => ['Cliente'], 'role_ids' => [1]],
        ];

        foreach ($invalidPayloads as $payload) {
            $this->apiPut('/api/v1/users/'.$user->id.'/roles', $admin, $payload)
                ->assertUnprocessable();
        }
    }

    public function test_index_supports_search_state_role_pagination_and_sort_whitelist(): void
    {
        $admin = $this->user('admin-user-index@example.test', 'Administrador');
        $client = $this->user('Ana Clienta', 'Cliente', 'Ana');
        $assistant = $this->user('Bruno Assistant', 'Asistente', 'Bruno');
        $assistant->update(['estado' => 'inactivo']);

        $this->apiGet('/api/v1/users?q=Ana', $admin)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $client->id);
        $this->apiGet('/api/v1/users?estado=inactivo', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.id', $assistant->id);
        $this->apiGet('/api/v1/users?role=Cliente', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.id', $client->id);
        $this->apiGet('/api/v1/users?sort=email&direction=asc', $admin)->assertOk();
        $this->apiGet('/api/v1/users?role=Público%20General', $admin)->assertUnprocessable();
        $this->apiGet('/api/v1/users?sort=arbitrary', $admin)->assertUnprocessable();
        $this->apiGet('/api/v1/users?direction=random', $admin)->assertUnprocessable();
    }

    public function test_index_pagination_default_and_maximum_are_enforced(): void
    {
        $admin = $this->user('admin-user-pagination@example.test', 'Administrador');

        for ($index = 0; $index < 16; $index++) {
            $this->user('pagination-user-'.$index.'@example.test', 'Cliente');
        }

        $this->apiGet('/api/v1/users', $admin)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15);
        $this->apiGet('/api/v1/users?per_page=100', $admin)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
        $this->apiGet('/api/v1/users?per_page=101', $admin)->assertUnprocessable();
    }

    public function test_soft_deleting_user_preserves_roles_and_profiles(): void
    {
        $admin = $this->user('admin-user-delete-profile@example.test', 'Administrador');
        $target = $this->user('target-user-profile@example.test', 'Agente Inmobiliario');
        $agent = Agente::create([
            'user_id' => $target->id,
            'numero_empleado' => 'USER-AGENT-001',
        ]);
        $client = Cliente::create([
            'user_id' => $target->id,
            'nombres' => 'Perfil',
            'apellido_paterno' => 'Cliente',
            'email' => 'target-client@example.test',
        ]);

        $this->apiDelete('/api/v1/users/'.$target->id, $admin)->assertNoContent();

        $deleted = User::withTrashed()->findOrFail($target->id);
        self::assertNotNull($deleted->deleted_at);
        self::assertTrue($deleted->hasRole('Agente Inmobiliario'));
        self::assertDatabaseHas('agentes', ['id' => $agent->id, 'user_id' => $target->id]);
        self::assertDatabaseHas('clientes', ['id' => $client->id, 'user_id' => $target->id]);
    }

    public function test_non_active_admin_is_rejected_by_account_active_middleware(): void
    {
        $admin = $this->user('admin-user-inactive@example.test', 'Administrador');
        $admin->update(['estado' => 'bloqueado']);

        $this->apiGet('/api/v1/users', $admin)->assertUnauthorized();
    }

    private function user(string $email, ?string $role = null, string $name = 'Usuario'): User
    {
        $user = User::create([
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    /** @param array<string, mixed> $payload */
    private function apiGet(string $uri, User $user)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }

    /** @param array<string, mixed> $payload */
    private function apiPost(string $uri, User $user, array $payload)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson($uri, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function apiPatch(string $uri, User $user, array $payload)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson($uri, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function apiPut(string $uri, User $user, array $payload)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->putJson($uri, $payload);
    }

    private function apiDelete(string $uri, User $user)
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson($uri);
    }
}
