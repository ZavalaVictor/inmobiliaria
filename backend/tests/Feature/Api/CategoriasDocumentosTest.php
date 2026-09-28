<?php

namespace Tests\Feature\Api;

use App\Models\CategoriaDocumento;
use App\Models\Documento;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CategoriasDocumentosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_categories_require_authentication_and_follow_the_approved_matrix(): void
    {
        $this->getJson('/api/v1/categorias-documentos')->assertUnauthorized();

        $admin = $this->user('category-doc-admin@example.test', 'Administrador');
        $agent = $this->user('category-doc-agent@example.test', 'Agente Inmobiliario');
        $category = $this->category('Lectura');
        self::assertFalse($agent->can('categorias_documentos.crear'));

        $this->apiGet('/api/v1/categorias-documentos', $admin)->assertOk();
        $created = $this->apiPost('/api/v1/categorias-documentos', $admin, [
            'nombre' => 'Administrativa',
            'descripcion' => 'Documentos administrativos',
        ])->assertCreated()->assertJsonPath('data.activo', true);

        $this->apiGet('/api/v1/categorias-documentos/'.$category->id, $agent)->assertOk();
        $this->apiPost('/api/v1/categorias-documentos', $agent, ['nombre' => 'No permitido'])
            ->assertForbidden();
        $this->apiPatch('/api/v1/categorias-documentos/'.$category->id, $agent, ['nombre' => 'No permitido'])
            ->assertForbidden();
        $this->apiDelete('/api/v1/categorias-documentos/'.$category->id, $agent)
            ->assertForbidden();

        foreach (['Asistente', 'Director General', 'Cliente'] as $role) {
            $user = $this->user(strtolower(str_replace(' ', '-', $role)).'@category-doc.example.test', $role);
            $this->apiGet('/api/v1/categorias-documentos', $user)->assertForbidden();
        }

        self::assertNotNull($created->json('data.id'));
    }

    public function test_category_validation_soft_deleted_unique_and_filters(): void
    {
        $admin = $this->user('category-doc-validation@example.test', 'Administrador');
        $deleted = $this->category('Reservada');
        $deleted->delete();

        $this->apiPost('/api/v1/categorias-documentos', $admin, ['nombre' => 'Reservada'])
            ->assertUnprocessable();
        $this->apiPost('/api/v1/categorias-documentos', $admin, [
            'nombre' => 'Inactiva',
            'activo' => false,
        ])->assertCreated();
        $this->apiPost('/api/v1/categorias-documentos', $admin, [
            'nombre' => str_repeat('x', 101),
        ])->assertUnprocessable();
        $this->apiPost('/api/v1/categorias-documentos', $admin, [
            'nombre' => 'Campos internos',
            'documentos' => [],
        ])->assertUnprocessable();

        $this->apiGet('/api/v1/categorias-documentos?activo=0', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Inactiva');
        $this->apiGet('/api/v1/categorias-documentos?q=Inactiva', $admin)
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Inactiva');
    }

    public function test_category_cannot_be_deleted_when_any_document_uses_it(): void
    {
        $admin = $this->user('category-doc-delete@example.test', 'Administrador');
        $category = $this->category('En uso');
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario documento',
            'rfc' => 'RFC'.strtoupper(substr(md5((string) mt_rand()), 0, 10)),
            'telefono' => '5555555555',
            'direccion' => 'Dirección',
        ]);
        $document = Documento::create([
            'categoria_documento_id' => $category->id,
            'subido_por_user_id' => $admin->id,
            'propietario_id' => $owner->id,
            'nombre_original' => 'contrato.pdf',
            'firebase_path' => 'documentos/propietarios/1/test.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->apiDelete('/api/v1/categorias-documentos/'.$category->id, $admin)
            ->assertUnprocessable();

        $document->delete();
        $this->apiDelete('/api/v1/categorias-documentos/'.$category->id, $admin)
            ->assertUnprocessable();
        self::assertNull($category->fresh()->deleted_at);
    }

    public function test_category_without_documents_can_be_soft_deleted_and_reactivated(): void
    {
        $admin = $this->user('category-doc-soft-delete@example.test', 'Administrador');
        $category = $this->category('Sin documentos');

        $this->apiPatch('/api/v1/categorias-documentos/'.$category->id, $admin, [
            'activo' => false,
        ])->assertOk()->assertJsonPath('data.activo', false);
        $this->apiDelete('/api/v1/categorias-documentos/'.$category->id, $admin)
            ->assertNoContent();

        self::assertSoftDeleted('categorias_documentos', ['id' => $category->id]);
        $this->apiGet('/api/v1/categorias-documentos/'.$category->id, $admin)->assertNotFound();
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Documento',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function category(string $name): CategoriaDocumento
    {
        return CategoriaDocumento::create(['nombre' => $name]);
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
