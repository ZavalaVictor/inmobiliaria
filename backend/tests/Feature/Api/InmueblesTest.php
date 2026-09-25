<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClienteInmuebleInteres;
use App\Models\Inmueble;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InmueblesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_user_cannot_list_properties(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/inmuebles')
            ->assertUnauthorized();
    }

    public function test_administrator_can_manage_properties_and_resource_is_safe(): void
    {
        $administrator = $this->user('admin-properties@example.test', 'Administrador');
        $owner = $this->owner('Administrador Propietario', 'AAA010101AAA');
        $category = $this->category('Residencial');

        $created = $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($owner, $category, [
            'codigo' => 'ADM-001',
            'slug' => 'adm-001',
            'titulo' => 'Casa administrativa',
            'latitud' => '19.4326080',
            'longitud' => '-99.1332090',
        ]))->assertCreated()
            ->assertJsonPath('data.codigo', 'ADM-001')
            ->assertJsonMissingPath('data.imagenes');

        $property = Inmueble::query()->where('codigo', 'ADM-001')->firstOrFail();

        $this->apiGet('/api/v1/inmuebles', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/inmuebles/'.$property->id, $administrator)
            ->assertOk()
            ->assertJsonPath('data.categoria.id', $category->id)
            ->assertJsonPath('data.propietario.id', $owner->id)
            ->assertJsonMissingPath('data.propietario.email')
            ->assertJsonMissingPath('data.agentes');

        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $administrator, [
            'titulo' => 'Casa actualizada',
        ])->assertOk()
            ->assertJsonPath('data.titulo', 'Casa actualizada');

        $this->apiDelete('/api/v1/inmuebles/'.$property->id, $administrator)
            ->assertNoContent();
        $this->apiGet('/api/v1/inmuebles/'.$property->id, $administrator)
            ->assertNotFound();
    }

    public function test_create_preserves_database_defaults(): void
    {
        $administrator = $this->user('admin-property-defaults@example.test', 'Administrador');
        $owner = $this->owner('Defaults Propietario', 'BBB020202BBB');
        $category = $this->category('Defaults Categoría');

        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($owner, $category, [
            'codigo' => 'DEF-001',
            'slug' => 'def-001',
        ]))->assertCreated();

        $property = Inmueble::query()->where('codigo', 'DEF-001')->firstOrFail();

        self::assertSame('disponible', $property->getRawOriginal('estado_disponibilidad'));
        self::assertFalse($property->publicado);
        self::assertSame(0, $property->habitaciones);
        self::assertSame(1, $property->niveles);
    }

    public function test_agent_only_sees_and_updates_assigned_properties(): void
    {
        $agentA = $this->agentUser('agent-property-a@example.test');
        $agentB = $this->agentUser('agent-property-b@example.test');
        $propertyA = $this->property('agent-a');
        $propertyB = $this->property('agent-b');
        $this->assignProperty($propertyA, $agentA->agente);
        $this->assignProperty($propertyB, $agentB->agente);

        $this->apiGet('/api/v1/inmuebles', $agentA)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $propertyA->id);
        $this->apiGet('/api/v1/inmuebles/'.$propertyA->id, $agentA)->assertOk();
        $this->apiGet('/api/v1/inmuebles/'.$propertyB->id, $agentA)->assertForbidden();

        $this->apiPatch('/api/v1/inmuebles/'.$propertyA->id, $agentA, [
            'titulo' => 'Actualizado por agente',
        ])->assertOk();
        $this->apiPatch('/api/v1/inmuebles/'.$propertyB->id, $agentA, [
            'titulo' => 'No permitido',
        ])->assertForbidden();
        $this->apiPost('/api/v1/inmuebles', $agentA, $this->payload(
            $propertyA->propietario,
            $propertyA->categoria,
            [
                'codigo' => 'AGENT-CREATE-FORBIDDEN',
                'slug' => 'agent-create-forbidden',
            ]
        ))->assertForbidden();
        $this->apiDelete('/api/v1/inmuebles/'.$propertyA->id, $agentA)
            ->assertForbidden();
    }

    public function test_agent_cannot_change_administrative_property_relations(): void
    {
        $agent = $this->agentUser('agent-property-relations@example.test');
        $property = $this->property('agent-relations');
        $otherOwner = $this->owner('Otro propietario', 'CCC030303CCC');
        $otherCategory = $this->category('Otra categoría');
        $this->assignProperty($property, $agent->agente);

        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $agent, [
            'propietario_id' => $otherOwner->id,
        ])->assertUnprocessable();
        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $agent, [
            'categoria_id' => $otherCategory->id,
        ])->assertUnprocessable();
    }

    public function test_assistant_can_create_and_update_but_not_delete(): void
    {
        $assistant = $this->user('assistant-properties@example.test', 'Asistente');
        $owner = $this->owner('Assistant Owner', 'DDD040404DDD');
        $category = $this->category('Assistant Category');
        $property = $this->property('assistant-existing');

        $this->apiGet('/api/v1/inmuebles', $assistant)->assertOk();
        $this->apiPost('/api/v1/inmuebles', $assistant, $this->payload($owner, $category, [
            'codigo' => 'AST-001',
            'slug' => 'ast-001',
        ]))->assertCreated();
        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $assistant, [
            'titulo' => 'Actualizado por asistente',
        ])->assertOk();
        $this->apiDelete('/api/v1/inmuebles/'.$property->id, $assistant)
            ->assertForbidden();
    }

    public function test_director_can_read_but_not_write_properties(): void
    {
        $director = $this->user('director-properties@example.test', 'Director General');
        $property = $this->property('director');

        $this->apiGet('/api/v1/inmuebles', $director)->assertOk();
        $this->apiGet('/api/v1/inmuebles/'.$property->id, $director)->assertOk();
        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $director, [
            'titulo' => 'No permitido',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/inmuebles/'.$property->id, $director)
            ->assertForbidden();
    }

    public function test_client_only_sees_properties_with_its_own_interest(): void
    {
        $clientUser = $this->user('client-properties@example.test', 'Cliente');
        $otherClientUser = $this->user('other-client-properties@example.test', 'Cliente');
        $client = Cliente::create([
            'user_id' => $clientUser->id,
            'nombres' => 'Cliente Portal',
            'apellido_paterno' => 'Prueba',
            'email' => 'client-properties@example.test',
        ]);
        $otherClient = Cliente::create([
            'user_id' => $otherClientUser->id,
            'nombres' => 'Otro Cliente',
            'apellido_paterno' => 'Prueba',
            'email' => 'other-client-properties@example.test',
        ]);
        $ownProperty = $this->property('client-own');
        $otherProperty = $this->property('client-other');
        $this->interest($client, $ownProperty);
        $this->interest($otherClient, $otherProperty);

        $this->apiGet('/api/v1/inmuebles', $clientUser)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownProperty->id)
            ->assertJsonMissingPath('data.0.propietario_id')
            ->assertJsonMissingPath('data.0.propietario');
        $this->apiGet('/api/v1/inmuebles/'.$ownProperty->id, $clientUser)->assertOk();
        $this->apiGet('/api/v1/inmuebles/'.$otherProperty->id, $clientUser)->assertForbidden();
        $this->apiPatch('/api/v1/inmuebles/'.$ownProperty->id, $clientUser, [
            'titulo' => 'No permitido',
        ])->assertForbidden();
    }

    public function test_property_relationships_must_reference_non_deleted_records(): void
    {
        $administrator = $this->user('admin-property-relations@example.test', 'Administrador');
        $owner = $this->owner('Deleted Owner', 'EEE050505EEE');
        $category = $this->category('Deleted Category');
        $owner->delete();
        $category->delete();

        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($owner, $this->category('Valid Category'), [
            'codigo' => 'REL-001',
            'slug' => 'rel-001',
        ]))->assertUnprocessable();
        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($this->owner('Valid Owner', 'FFF060606FFF'), $category, [
            'codigo' => 'REL-002',
            'slug' => 'rel-002',
        ]))->assertUnprocessable();
        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($this->owner('Missing Owner', 'GGG070707GGG'), $this->category('Missing Category'), [
            'propietario_id' => 999999,
            'codigo' => 'REL-003',
            'slug' => 'rel-003',
        ]))->assertUnprocessable();
    }

    public function test_index_filters_reject_soft_deleted_category_and_owner(): void
    {
        $administrator = $this->user('admin-property-soft-delete-filters@example.test', 'Administrador');
        $category = $this->category('Deleted Filter Category');
        $owner = $this->owner('Deleted Filter Owner', 'ZZZ260926ZZZ');

        $category->delete();
        $owner->delete();

        $this->apiGet('/api/v1/inmuebles?categoria_id='.$category->id, $administrator)
            ->assertUnprocessable();
        $this->apiGet('/api/v1/inmuebles?propietario_id='.$owner->id, $administrator)
            ->assertUnprocessable();
    }

    public function test_operation_prices_are_validated(): void
    {
        $administrator = $this->user('admin-property-prices@example.test', 'Administrador');
        $owner = $this->owner('Prices Owner', 'HHH080808HHH');
        $category = $this->category('Prices Category');

        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($owner, $category, [
            'codigo' => 'PRICE-VENTA',
            'slug' => 'price-venta',
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'renta_mensual' => null,
        ]))->assertCreated();
        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($owner, $category, [
            'codigo' => 'PRICE-RENTA',
            'slug' => 'price-renta',
            'tipo_operacion' => 'renta',
            'precio_venta' => null,
            'renta_mensual' => '10000.00',
        ]))->assertCreated();

        foreach ([
            ['tipo_operacion' => 'venta', 'precio_venta' => null, 'renta_mensual' => null],
            ['tipo_operacion' => 'venta', 'precio_venta' => '100.00', 'renta_mensual' => '10.00'],
            ['tipo_operacion' => 'renta', 'precio_venta' => null, 'renta_mensual' => null],
            ['tipo_operacion' => 'renta', 'precio_venta' => '100.00', 'renta_mensual' => '10.00'],
            ['tipo_operacion' => 'venta', 'precio_venta' => '0.00', 'renta_mensual' => null],
            ['tipo_operacion' => 'renta', 'precio_venta' => null, 'renta_mensual' => '-1.00'],
            ['tipo_operacion' => 'venta', 'precio_venta' => '1000000000000.00', 'renta_mensual' => null],
        ] as $index => $prices) {
            $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($owner, $category, [
                'codigo' => 'PRICE-INVALID-'.$index,
                'slug' => 'price-invalid-'.$index,
                ...$prices,
            ]))->assertUnprocessable();
        }
    }

    public function test_patch_preserves_operation_price_invariants(): void
    {
        $administrator = $this->user('admin-property-patch-prices@example.test', 'Administrador');
        $property = $this->property('patch-prices');

        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $administrator, [
            'tipo_operacion' => 'renta',
        ])->assertUnprocessable();

        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $administrator, [
            'tipo_operacion' => 'renta',
            'precio_venta' => null,
            'renta_mensual' => '12000.00',
        ])->assertOk();

        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $administrator, [
            'tipo_operacion' => 'venta',
        ])->assertUnprocessable();

        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $administrator, [
            'tipo_operacion' => 'venta',
            'precio_venta' => '250000.00',
            'renta_mensual' => null,
        ])->assertOk();
    }

    public function test_surfaces_coordinates_and_enums_are_validated(): void
    {
        $administrator = $this->user('admin-property-measures@example.test', 'Administrador');
        $owner = $this->owner('Measures Owner', 'III090909III');
        $category = $this->category('Measures Category');

        foreach ([
            ['superficie_terreno_m2' => '0.00'],
            ['superficie_terreno_m2' => '-1.00'],
            ['superficie_construccion_m2' => '-1.00'],
            ['superficie_construccion_m2' => '100000000.00'],
            ['latitud' => '19.12345678', 'longitud' => '-99.1234567'],
            ['latitud' => '91.0000000', 'longitud' => null],
            ['latitud' => null, 'longitud' => '-181.0000000'],
            ['tipo_operacion' => 'invalid'],
            ['estado_disponibilidad' => 'invalid'],
        ] as $index => $invalid) {
            $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($owner, $category, [
                'codigo' => 'MEASURE-INVALID-'.$index,
                'slug' => 'measure-invalid-'.$index,
                ...$invalid,
            ]))->assertUnprocessable();
        }

        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($owner, $category, [
            'codigo' => 'MEASURE-VALID',
            'slug' => 'measure-valid',
            'superficie_terreno_m2' => '0.01',
            'superficie_construccion_m2' => '0.00',
            'latitud' => '19.4326080',
            'longitud' => '-99.1332090',
        ]))->assertCreated();
    }

    public function test_coordinates_cannot_become_partial_during_patch(): void
    {
        $administrator = $this->user('admin-property-coordinates@example.test', 'Administrador');
        $property = $this->property('coordinates', [
            'latitud' => '19.4326080',
            'longitud' => '-99.1332090',
        ]);

        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $administrator, [
            'latitud' => null,
        ])->assertUnprocessable();
        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $administrator, [
            'latitud' => null,
            'longitud' => null,
        ])->assertOk();
    }

    public function test_codes_and_slugs_are_unique_and_soft_deleted_values_remain_reserved(): void
    {
        $administrator = $this->user('admin-property-unique@example.test', 'Administrador');
        $first = $this->property('unique-first');
        $second = $this->property('unique-second');

        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($first->propietario, $first->categoria, [
            'codigo' => $first->codigo,
            'slug' => 'unique-new',
        ]))->assertUnprocessable();
        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($second->propietario, $second->categoria, [
            'codigo' => 'unique-new',
            'slug' => $first->slug,
        ]))->assertUnprocessable();

        $this->apiPatch('/api/v1/inmuebles/'.$first->id, $administrator, [
            'codigo' => $first->codigo,
            'slug' => $first->slug,
        ])->assertOk();

        $first->delete();
        $this->apiPost('/api/v1/inmuebles', $administrator, $this->payload($second->propietario, $second->categoria, [
            'codigo' => $first->codigo,
            'slug' => $first->slug,
        ]))->assertUnprocessable();
    }

    public function test_prohibited_fields_do_not_modify_relationships(): void
    {
        $administrator = $this->user('admin-property-prohibited@example.test', 'Administrador');
        $property = $this->property('prohibited');

        foreach ([
            'agente_id',
            'es_principal',
            'asignaciones',
            'agentes',
            'imagenes',
            'documentos',
            'clientes',
            'intereses',
            'roles',
            'permisos',
            'created_at',
            'updated_at',
            'deleted_at',
        ] as $field) {
            $this->apiPatch('/api/v1/inmuebles/'.$property->id, $administrator, [
                $field => ['valor'],
            ])->assertUnprocessable();
        }

        self::assertSame(0, $property->asignacionesAgentes()->count());
        self::assertSame(0, $property->imagenes()->count());
    }

    public function test_index_filters_do_not_broaden_visibility(): void
    {
        $agentA = $this->agentUser('agent-property-search-a@example.test');
        $agentB = $this->agentUser('agent-property-search-b@example.test');
        $propertyA = $this->property('search-a', [
            'titulo' => 'Visible A',
            'municipio' => 'Ciudad A',
            'habitaciones' => 3,
            'banos_completos' => 2,
        ]);
        $propertyB = $this->property('search-b', [
            'titulo' => 'Coincidencia B',
            'municipio' => 'Ciudad B',
        ]);
        $this->assignProperty($propertyA, $agentA->agente);
        $this->assignProperty($propertyB, $agentB->agente);

        $this->apiGet('/api/v1/inmuebles?q=Coincidencia', $agentA)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/inmuebles?municipio=Ciudad B', $agentA)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/inmuebles?habitaciones=3', $agentA)
            ->assertOk()
            ->assertJsonPath('data.0.id', $propertyA->id);
    }

    public function test_index_supports_all_approved_filters_and_pagination(): void
    {
        $administrator = $this->user('admin-property-filters@example.test', 'Administrador');
        $owner = $this->owner('Filter Owner', 'JJJ101010JJJ');
        $category = $this->category('Filter Category');
        $property = $this->property('filters', [
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'tipo_operacion' => 'renta',
            'precio_venta' => null,
            'renta_mensual' => '9000.00',
            'estado_disponibilidad' => 'rentado',
            'habitaciones' => 4,
            'banos_completos' => 3,
            'publicado' => true,
            'municipio' => 'Filtro Municipio',
            'estado_ubicacion' => 'Filtro Estado',
        ]);

        $this->apiGet('/api/v1/inmuebles?tipo_operacion=renta', $administrator)
            ->assertOk()->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/inmuebles?estado_disponibilidad=rentado', $administrator)
            ->assertOk()->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/inmuebles?categoria_id='.$category->id, $administrator)
            ->assertOk()->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/inmuebles?propietario_id='.$owner->id, $administrator)
            ->assertOk()->assertJsonCount(1, 'data');
        $this->apiGet('/api/v1/inmuebles?publicado=1&estado_ubicacion=Filtro%20Estado', $administrator)
            ->assertOk()->assertJsonPath('data.0.id', $property->id);
        $this->apiGet('/api/v1/inmuebles?sort=titulo&direction=asc', $administrator)
            ->assertOk();
        $this->apiGet('/api/v1/inmuebles?sort=invalid', $administrator)
            ->assertUnprocessable();
        $this->apiGet('/api/v1/inmuebles?direction=random', $administrator)
            ->assertUnprocessable();
        $this->apiGet('/api/v1/inmuebles?per_page=101', $administrator)
            ->assertUnprocessable();
    }

    public function test_index_uses_default_and_maximum_pagination_sizes(): void
    {
        $administrator = $this->user('admin-property-pagination@example.test', 'Administrador');

        for ($index = 1; $index <= 16; $index++) {
            $this->property('pagination-'.$index);
        }

        $this->apiGet('/api/v1/inmuebles', $administrator)
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2);
        $this->apiGet('/api/v1/inmuebles?per_page=100', $administrator)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.total', 16);
    }

    public function test_soft_deleted_properties_are_not_listed_or_resolved(): void
    {
        $administrator = $this->user('admin-property-soft-delete@example.test', 'Administrador');
        $property = $this->property('soft-deleted');
        $property->delete();

        $this->apiGet('/api/v1/inmuebles', $administrator)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/inmuebles/'.$property->id, $administrator)
            ->assertNotFound();
    }

    public function test_global_multi_role_user_keeps_global_update_scope(): void
    {
        $user = $this->user('multi-role-property@example.test', 'Administrador');
        $user->assignRole('Agente Inmobiliario');
        $user = $user->fresh(['agente']);

        $property = $this->property('multi-role');
        $otherOwner = $this->owner('Multi Role Owner', 'YYY250925YYY');
        $otherCategory = $this->category('Multi Role Category');

        $this->apiPatch('/api/v1/inmuebles/'.$property->id, $user, [
            'propietario_id' => $otherOwner->id,
            'categoria_id' => $otherCategory->id,
        ])->assertOk();

        $property->refresh();
        self::assertSame($otherOwner->id, $property->propietario_id);
        self::assertSame($otherCategory->id, $property->categoria_id);
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

    private function owner(string $name, string $rfc): Propietario
    {
        return Propietario::create([
            'nombre_razon_social' => $name,
            'rfc' => $rfc,
            'telefono' => '5555555555',
            'direccion' => 'Calle Propietario 1',
        ]);
    }

    private function category(string $name): Categoria
    {
        return Categoria::create(['nombre' => $name]);
    }

    /** @param array<string, mixed> $overrides */
    private function property(string $suffix, array $overrides = []): Inmueble
    {
        $owner = $this->owner('Owner '.$suffix, strtoupper(substr(md5('owner-'.$suffix), 0, 13)));
        $category = $this->category('Category '.$suffix);

        return Inmueble::create(array_merge([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'PROP-'.$suffix,
            'titulo' => 'Property '.$suffix,
            'slug' => 'property-'.$suffix,
            'descripcion' => 'Descripción '.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle Principal 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function payload(Propietario $owner, Categoria $category, array $overrides = []): array
    {
        return array_merge([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'API-CODE',
            'titulo' => 'API Property',
            'slug' => 'api-property',
            'descripcion' => 'Descripción API',
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle API 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
        ], $overrides);
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

    private function interest(Cliente $client, Inmueble $property): ClienteInmuebleInteres
    {
        return ClienteInmuebleInteres::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'nivel_interes' => 'medio',
            'estado' => 'activo',
            'fecha_interes' => now(),
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
