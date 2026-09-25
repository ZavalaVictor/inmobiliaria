<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PropietariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_owners(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/propietarios')
            ->assertUnauthorized();
    }

    public function test_administrator_can_list_create_show_update_and_delete_owner(): void
    {
        $administrator = $this->user('admin-owners@example.test', 'Administrador');

        $created = $this->apiPost('/api/v1/propietarios', $administrator, [
            'tipo_persona' => 'moral',
            'nombre_razon_social' => 'Inmobiliaria Central',
            'rfc' => 'ABC010101AAA',
            'telefono' => '5555555555',
            'email' => 'central@example.test',
            'direccion' => 'Avenida Central 100',
            'estado_registro' => 'activo',
        ])->assertCreated()
            ->assertJsonPath('data.tipo_persona', 'moral')
            ->assertJsonPath('data.nombre_razon_social', 'Inmobiliaria Central');

        $propietario = Propietario::query()->where('rfc', 'ABC010101AAA')->firstOrFail();

        $this->apiGet('/api/v1/propietarios', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->apiGet('/api/v1/propietarios/'.$propietario->id, $administrator)
            ->assertOk()
            ->assertJsonPath('data.id', $created->json('data.id'));

        $this->apiPatch('/api/v1/propietarios/'.$propietario->id, $administrator, [
            'nombre_razon_social' => 'Inmobiliaria Actualizada',
        ])->assertOk()
            ->assertJsonPath('data.nombre_razon_social', 'Inmobiliaria Actualizada');

        $this->apiDelete('/api/v1/propietarios/'.$propietario->id, $administrator)
            ->assertNoContent();

        $this->apiGet('/api/v1/propietarios/'.$propietario->id, $administrator)
            ->assertNotFound();
    }

    public function test_create_uses_database_defaults_for_type_and_status(): void
    {
        $administrator = $this->user('admin-owner-defaults@example.test', 'Administrador');

        $this->apiPost('/api/v1/propietarios', $administrator, [
            'nombre_razon_social' => 'Propietario con defaults',
            'rfc' => 'DEF010101AAA',
            'telefono' => '5555555555',
            'direccion' => 'Calle Default 10',
        ])->assertCreated();

        $propietario = Propietario::query()->where('rfc', 'DEF010101AAA')->firstOrFail();

        self::assertSame('fisica', $propietario->getRawOriginal('tipo_persona'));
        self::assertSame('activo', $propietario->getRawOriginal('estado_registro'));
    }

    public function test_rfc_must_be_unique_but_owner_can_keep_its_own_rfc(): void
    {
        $administrator = $this->user('admin-owner-rfc@example.test', 'Administrador');
        $first = $this->owner('Primer propietario', 'RFC010101AAA');
        $second = $this->owner('Segundo propietario', 'RFC020202AAA');

        $this->apiPost('/api/v1/propietarios', $administrator, [
            'nombre_razon_social' => 'Duplicado',
            'rfc' => $first->rfc,
            'telefono' => '5555555555',
            'direccion' => 'Calle Duplicada 1',
        ])->assertUnprocessable();

        $this->apiPatch('/api/v1/propietarios/'.$first->id, $administrator, [
            'rfc' => $first->rfc,
        ])->assertOk();

        $this->apiPatch('/api/v1/propietarios/'.$first->id, $administrator, [
            'rfc' => $second->rfc,
        ])->assertUnprocessable();
    }

    public function test_agent_only_sees_owners_related_through_assigned_properties(): void
    {
        $agentA = $this->agentUser('agent-owner-a@example.test');
        $agentB = $this->agentUser('agent-owner-b@example.test');
        $ownerA = $this->owner('Propietario A', 'AAA010101AAA');
        $ownerB = $this->owner('Propietario SoloB', 'BBB020202BBB');
        $propertyA = $this->property($ownerA, 'owner-a');
        $propertyB = $this->property($ownerB, 'owner-b');
        $this->assignProperty($propertyA, $agentA->agente);
        $this->assignProperty($propertyB, $agentB->agente);

        $this->apiGet('/api/v1/propietarios', $agentA)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownerA->id);

        $this->apiGet('/api/v1/propietarios/'.$ownerA->id, $agentA)->assertOk();
        $this->apiGet('/api/v1/propietarios/'.$ownerB->id, $agentA)->assertForbidden();

        $this->apiGet('/api/v1/propietarios?q=SoloB', $agentA)
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonMissing(['id' => $ownerB->id]);
    }

    public function test_agent_cannot_create_update_or_delete_owners(): void
    {
        $agent = $this->agentUser('agent-owner-write@example.test');
        $owner = $this->owner('Propietario visible', 'CCC030303CCC');
        $property = $this->property($owner, 'owner-write');
        $this->assignProperty($property, $agent->agente);

        $this->apiPost('/api/v1/propietarios', $agent, [
            'nombre_razon_social' => 'No permitido',
            'rfc' => 'DDD040404DDD',
            'telefono' => '5555555555',
            'direccion' => 'Calle No Permitida 1',
        ])->assertForbidden();

        $this->apiPatch('/api/v1/propietarios/'.$owner->id, $agent, [
            'nombre_razon_social' => 'No permitido',
        ])->assertForbidden();

        $this->apiDelete('/api/v1/propietarios/'.$owner->id, $agent)
            ->assertForbidden();
    }

    public function test_assistant_can_read_create_and_update_but_not_delete(): void
    {
        $assistant = $this->user('assistant-owners@example.test', 'Asistente');
        $owner = $this->owner('Propietario asistente', 'EEE050505EEE');

        $this->apiGet('/api/v1/propietarios', $assistant)->assertOk();
        $this->apiPost('/api/v1/propietarios', $assistant, [
            'nombre_razon_social' => 'Creado por asistente',
            'rfc' => 'FFF060606FFF',
            'telefono' => '5555555555',
            'direccion' => 'Calle Asistente 1',
        ])->assertCreated();

        $this->apiPatch('/api/v1/propietarios/'.$owner->id, $assistant, [
            'nombre_razon_social' => 'Actualizado por asistente',
        ])->assertOk();

        $this->apiDelete('/api/v1/propietarios/'.$owner->id, $assistant)
            ->assertForbidden();
    }

    public function test_director_can_read_but_not_write_owners(): void
    {
        $director = $this->user('director-owners@example.test', 'Director General');
        $owner = $this->owner('Propietario director', 'GGG070707GGG');

        $this->apiGet('/api/v1/propietarios', $director)->assertOk();
        $this->apiGet('/api/v1/propietarios/'.$owner->id, $director)->assertOk();
        $this->apiPost('/api/v1/propietarios', $director, [
            'nombre_razon_social' => 'No permitido',
            'rfc' => 'HHH080808HHH',
            'telefono' => '5555555555',
            'direccion' => 'Calle Director 1',
        ])->assertForbidden();
        $this->apiPatch('/api/v1/propietarios/'.$owner->id, $director, [
            'nombre_razon_social' => 'No permitido',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/propietarios/'.$owner->id, $director)
            ->assertForbidden();
    }

    public function test_client_cannot_access_owners(): void
    {
        $client = $this->user('client-owners@example.test', 'Cliente');
        $owner = $this->owner('Propietario privado', 'III090909III');

        $this->apiGet('/api/v1/propietarios', $client)->assertForbidden();
        $this->apiGet('/api/v1/propietarios/'.$owner->id, $client)->assertForbidden();
    }

    public function test_validation_rejects_invalid_values_and_lengths(): void
    {
        $administrator = $this->user('admin-owner-validation@example.test', 'Administrador');

        foreach ([
            ['tipo_persona' => 'invalid'],
            ['estado_registro' => 'invalid'],
            ['email' => 'not-an-email'],
            ['nombre_razon_social' => str_repeat('N', 151)],
            ['rfc' => str_repeat('R', 14)],
            ['telefono' => str_repeat('5', 21)],
            ['direccion' => str_repeat('D', 256)],
        ] as $invalid) {
            $this->apiPost('/api/v1/propietarios', $administrator, [
                'nombre_razon_social' => 'Propietario inválido',
                'rfc' => 'JJJ101010JJJ',
                'telefono' => '5555555555',
                'direccion' => 'Calle Validación 1',
                ...$invalid,
            ])->assertUnprocessable();
        }
    }

    public function test_create_rejects_prohibited_fields_without_creating_properties(): void
    {
        $administrator = $this->user('admin-owner-prohibited@example.test', 'Administrador');
        $initialProperties = Inmueble::query()->count();

        foreach (['id', 'created_at', 'updated_at', 'deleted_at', 'inmuebles', 'relaciones', 'roles', 'permisos'] as $field) {
            $this->apiPost('/api/v1/propietarios', $administrator, [
                'nombre_razon_social' => 'Campo prohibido',
                'rfc' => 'KKK111111KKK',
                'telefono' => '5555555555',
                'direccion' => 'Calle Prohibida 1',
                $field => $field === 'id' ? 999999 : ['valor'],
            ])->assertUnprocessable();
        }

        self::assertSame($initialProperties, Inmueble::query()->count());
    }

    public function test_index_supports_search_enum_filters_pagination_and_sort_whitelist(): void
    {
        $administrator = $this->user('admin-owner-filters@example.test', 'Administrador');
        $this->owner('Búsqueda RFC', 'LLL121212LLL', 'moral', 'inactivo');
        $this->owner('Otro propietario', 'MMM131313MMM');

        $this->apiGet('/api/v1/propietarios?q=LLL121212LLL', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/propietarios?tipo_persona=moral', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/propietarios?estado_registro=inactivo', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/propietarios?sort=nombre_razon_social&direction=asc', $administrator)
            ->assertOk();
        $this->apiGet('/api/v1/propietarios?sort=not_a_column', $administrator)
            ->assertUnprocessable();
        $this->apiGet('/api/v1/propietarios?direction=random', $administrator)
            ->assertUnprocessable();
        $this->apiGet('/api/v1/propietarios?per_page=101', $administrator)
            ->assertUnprocessable();
    }

    public function test_index_uses_default_and_maximum_pagination_sizes(): void
    {
        $administrator = $this->user('admin-owner-pagination@example.test', 'Administrador');

        for ($index = 1; $index <= 16; $index++) {
            $this->owner('Propietario '.$index, 'N'.str_pad((string) $index, 2, '0', STR_PAD_LEFT).'1414NNN');
        }

        $this->apiGet('/api/v1/propietarios', $administrator)
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2);

        $this->apiGet('/api/v1/propietarios?per_page=100', $administrator)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.total', 16);
    }

    public function test_soft_deleted_owners_are_not_listed_or_resolved(): void
    {
        $administrator = $this->user('admin-owner-soft-delete@example.test', 'Administrador');
        $owner = $this->owner('Propietario eliminado', 'OOO151515OOO');
        $owner->delete();

        $this->apiGet('/api/v1/propietarios', $administrator)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/propietarios/'.$owner->id, $administrator)
            ->assertNotFound();
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

    private function agentUser(string $email): User
    {
        $user = $this->user($email, 'Agente Inmobiliario');
        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => strtoupper(substr(md5($email), 0, 10)),
        ]);

        return $user->fresh(['agente']);
    }

    private function owner(
        string $name,
        string $rfc,
        string $type = 'fisica',
        string $status = 'activo'
    ): Propietario {
        return Propietario::create([
            'tipo_persona' => $type,
            'nombre_razon_social' => $name,
            'rfc' => $rfc,
            'telefono' => '5555555555',
            'email' => strtolower($rfc).'@example.test',
            'direccion' => 'Calle Propietario 1',
            'estado_registro' => $status,
        ]);
    }

    private function property(Propietario $owner, string $suffix): Inmueble
    {
        $category = Categoria::create([
            'nombre' => 'Categoría '.$suffix,
        ]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'COD-'.$suffix,
            'titulo' => 'Inmueble '.$suffix,
            'slug' => 'inmueble-'.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle Inmueble 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
        ]);
    }

    private function assignProperty(Inmueble $property, Agente $agent): AgenteInmueble
    {
        return AgenteInmueble::create([
            'agente_id' => $agent->id,
            'inmueble_id' => $property->id,
            'es_principal' => false,
            'fecha_asignacion' => now(),
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
