<?php

namespace Tests\Feature\Api;

use App\Models\Categoria;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CategoriasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_categories(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/categorias')
            ->assertUnauthorized();
    }

    public function test_administrator_can_list_create_show_update_and_delete_category(): void
    {
        $administrator = $this->user('admin-categories@example.test', 'Administrador');

        $created = $this->apiPost('/api/v1/categorias', $administrator, [
            'nombre' => 'Residencial',
            'descripcion' => 'Categoría residencial',
            'activo' => true,
        ])->assertCreated()
            ->assertJsonPath('data.nombre', 'Residencial')
            ->assertJsonPath('data.activo', true)
            ->assertJsonMissingPath('data.inmuebles');

        $categoria = Categoria::query()->where('nombre', 'Residencial')->firstOrFail();

        $this->apiGet('/api/v1/categorias', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/categorias/'.$categoria->id, $administrator)
            ->assertOk()
            ->assertJsonPath('data.id', $created->json('data.id'));

        $this->apiPatch('/api/v1/categorias/'.$categoria->id, $administrator, [
            'nombre' => 'Residencial Actualizada',
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Residencial Actualizada');

        $this->apiDelete('/api/v1/categorias/'.$categoria->id, $administrator)
            ->assertNoContent();

        $this->apiGet('/api/v1/categorias/'.$categoria->id, $administrator)
            ->assertNotFound();
    }

    public function test_all_authenticated_roles_can_read_categories_but_only_administrator_writes(): void
    {
        $categoria = $this->category('Catálogo global');

        foreach ([
            'Agente Inmobiliario',
            'Asistente',
            'Director General',
            'Cliente',
        ] as $role) {
            $user = $this->user(strtolower(str_replace(' ', '-', $role)).'@categories.example.test', $role);

            $this->apiGet('/api/v1/categorias', $user)->assertOk();
            $this->apiGet('/api/v1/categorias/'.$categoria->id, $user)->assertOk();
            $this->apiPost('/api/v1/categorias', $user, [
                'nombre' => 'No permitido '.$role,
            ])->assertForbidden();
            $this->apiPatch('/api/v1/categorias/'.$categoria->id, $user, [
                'nombre' => 'No permitido '.$role,
            ])->assertForbidden();
            $this->apiDelete('/api/v1/categorias/'.$categoria->id, $user)
                ->assertForbidden();
        }
    }

    public function test_create_uses_database_default_for_activo(): void
    {
        $administrator = $this->user('admin-category-default@example.test', 'Administrador');

        $this->apiPost('/api/v1/categorias', $administrator, [
            'nombre' => 'Con default',
        ])->assertCreated();

        $categoria = Categoria::query()->where('nombre', 'Con default')->firstOrFail();

        self::assertTrue($categoria->activo);
    }

    public function test_category_name_must_be_unique_and_can_keep_its_own_name(): void
    {
        $administrator = $this->user('admin-category-unique@example.test', 'Administrador');
        $first = $this->category('Categoría única');
        $second = $this->category('Otra categoría');

        $this->apiPost('/api/v1/categorias', $administrator, [
            'nombre' => $first->nombre,
        ])->assertUnprocessable();

        $this->apiPatch('/api/v1/categorias/'.$first->id, $administrator, [
            'nombre' => $first->nombre,
        ])->assertOk();

        $this->apiPatch('/api/v1/categorias/'.$first->id, $administrator, [
            'nombre' => $second->nombre,
        ])->assertUnprocessable();
    }

    public function test_validation_rejects_missing_invalid_and_oversized_values(): void
    {
        $administrator = $this->user('admin-category-validation@example.test', 'Administrador');

        $this->apiPost('/api/v1/categorias', $administrator, [])->assertUnprocessable();
        $this->apiPost('/api/v1/categorias', $administrator, [
            'nombre' => str_repeat('N', 81),
        ])->assertUnprocessable();
        $this->apiPost('/api/v1/categorias', $administrator, [
            'nombre' => 'Descripción larga',
            'descripcion' => str_repeat('D', 256),
        ])->assertUnprocessable();
        $this->apiPost('/api/v1/categorias', $administrator, [
            'nombre' => 'Activo inválido',
            'activo' => 'not-a-boolean',
        ])->assertUnprocessable();

        $categoria = $this->category('Descripción nullable');
        $this->apiPatch('/api/v1/categorias/'.$categoria->id, $administrator, [
            'descripcion' => null,
        ])->assertOk()->assertJsonPath('data.descripcion', null);
    }

    public function test_prohibited_fields_are_rejected_without_creating_relations(): void
    {
        $administrator = $this->user('admin-category-prohibited@example.test', 'Administrador');
        $initialProperties = Inmueble::query()->count();

        foreach (['id', 'created_at', 'updated_at', 'deleted_at', 'inmuebles', 'relaciones', 'roles', 'permisos'] as $field) {
            $this->apiPost('/api/v1/categorias', $administrator, [
                'nombre' => 'Campo prohibido',
                $field => $field === 'id' ? 999999 : ['valor'],
            ])->assertUnprocessable();
        }

        self::assertSame($initialProperties, Inmueble::query()->count());
    }

    public function test_index_supports_search_pagination_and_sort_whitelist(): void
    {
        $administrator = $this->user('admin-category-index@example.test', 'Administrador');
        $this->category('Especial', 'Descripción buscable');
        $this->category('Otra categoría');

        $this->apiGet('/api/v1/categorias?q=buscable', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/categorias?sort=nombre&direction=asc', $administrator)
            ->assertOk();
        $this->apiGet('/api/v1/categorias?sort=invalid', $administrator)
            ->assertUnprocessable();
        $this->apiGet('/api/v1/categorias?direction=random', $administrator)
            ->assertUnprocessable();
        $this->apiGet('/api/v1/categorias?per_page=101', $administrator)
            ->assertUnprocessable();
    }

    public function test_index_uses_default_and_maximum_pagination_sizes(): void
    {
        $administrator = $this->user('admin-category-pagination@example.test', 'Administrador');

        for ($index = 1; $index <= 16; $index++) {
            $this->category('Paginación '.$index);
        }

        $this->apiGet('/api/v1/categorias', $administrator)
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2);

        $this->apiGet('/api/v1/categorias?per_page=100', $administrator)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.total', 16);
    }

    public function test_category_without_active_properties_can_be_soft_deleted(): void
    {
        $administrator = $this->user('admin-category-soft-delete@example.test', 'Administrador');
        $categoria = $this->category('Eliminable');

        $this->apiDelete('/api/v1/categorias/'.$categoria->id, $administrator)
            ->assertNoContent();

        $this->apiGet('/api/v1/categorias', $administrator)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/categorias/'.$categoria->id, $administrator)
            ->assertNotFound();
    }

    public function test_category_with_active_property_cannot_be_deleted(): void
    {
        $administrator = $this->user('admin-category-in-use@example.test', 'Administrador');
        $categoria = $this->category('En uso');
        $property = $this->propertyForCategory($categoria, 'active');

        $this->apiDelete('/api/v1/categorias/'.$categoria->id, $administrator)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['categoria']);

        self::assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'deleted_at' => null,
        ]);
        self::assertDatabaseHas('inmuebles', [
            'id' => $property->id,
            'deleted_at' => null,
        ]);
    }

    public function test_category_can_be_deleted_when_related_properties_are_soft_deleted(): void
    {
        $administrator = $this->user('admin-category-inactive-property@example.test', 'Administrador');
        $categoria = $this->category('Solo propiedades eliminadas');
        $property = $this->propertyForCategory($categoria, 'deleted');
        $property->delete();

        $this->apiDelete('/api/v1/categorias/'.$categoria->id, $administrator)
            ->assertNoContent();

        self::assertSoftDeleted('categorias', ['id' => $categoria->id]);
        self::assertSoftDeleted('inmuebles', ['id' => $property->id]);
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

    private function category(string $name, ?string $description = null, bool $active = true): Categoria
    {
        return Categoria::create([
            'nombre' => $name,
            'descripcion' => $description,
            'activo' => $active,
        ]);
    }

    private function propertyForCategory(Categoria $category, string $suffix): Inmueble
    {
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario '.$suffix,
            'rfc' => strtoupper(substr(md5('rfc-'.$suffix), 0, 13)),
            'telefono' => '5555555555',
            'direccion' => 'Calle Categoría 1',
        ]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'CAT-'.$suffix,
            'titulo' => 'Inmueble '.$suffix,
            'slug' => 'inmueble-categoria-'.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle Principal 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
        ]);
    }

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
}
