<?php

namespace Tests\Feature\Api;

use App\Models\Categoria;
use App\Models\Inmueble;
use App\Models\InmuebleImagen;
use App\Models\Propietario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicInmueblesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_returns_only_published_properties_and_safe_fields(): void
    {
        $published = $this->property('publicado', true, [
            'latitud' => '19.4326080',
            'longitud' => '-99.1332090',
        ]);
        $this->property('no-publicado', false);
        $image = InmuebleImagen::create([
            'inmueble_id' => $published->id,
            'firebase_path' => 'inmuebles/'.$published->id.'/fachada.jpg',
            'url_publica' => 'https://storage.googleapis.com/example/fachada.jpg',
            'nombre_original' => 'fachada.jpg',
            'mime_type' => 'image/jpeg',
            'es_principal' => true,
            'orden' => 0,
            'texto_alternativo' => 'Fachada principal',
        ]);

        $this->getJson('/api/v1/public/inmuebles')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'codigo',
                    'titulo',
                    'slug',
                    'descripcion',
                    'tipo_operacion',
                    'precio',
                    'moneda',
                    'coordenadas' => ['latitud', 'longitud'],
                    'categoria',
                    'imagen_principal' => ['url_publica', 'texto_alternativo'],
                ]],
                'links',
                'meta',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonPath('data.0.coordenadas.latitud', 19.433)
            ->assertJsonPath('data.0.coordenadas.longitud', -99.133)
            ->assertJsonPath('data.0.imagen_principal.url_publica', $image->url_publica)
            ->assertJsonMissingPath('data.0.propietario')
            ->assertJsonMissingPath('data.0.calle')
            ->assertJsonMissingPath('data.0.firebase_path');
    }

    public function test_public_listing_returns_null_coordinates_when_property_has_no_map_location(): void
    {
        $published = $this->property('sin-coordenadas', true);

        $this->getJson('/api/v1/public/inmuebles')
            ->assertOk()
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonPath('data.0.coordenadas', null);
    }

    public function test_public_listing_supports_safe_filters_and_keeps_published_scope(): void
    {
        $this->property('public-sale', true, ['tipo_operacion' => 'venta']);
        $rental = $this->property('public-rent', true, [
            'tipo_operacion' => 'renta',
            'precio_venta' => null,
            'renta_mensual' => '15000.00',
            'municipio' => 'Cuernavaca',
        ]);

        $this->getJson('/api/v1/public/inmuebles?tipo_operacion=renta&municipio=Cuernavaca')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $rental->id);

        $this->getJson('/api/v1/public/inmuebles?publicado=false')
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/public/inmuebles?per_page=51')
            ->assertUnprocessable();
    }

    public function test_public_listing_supports_location_type_and_price_filters(): void
    {
        $matching = $this->property('combined-match', true, [
            'tipo_operacion' => 'venta',
            'precio_venta' => '2500000.00',
            'municipio' => 'Cuernavaca',
        ]);
        $this->property('combined-other-city', true, [
            'tipo_operacion' => 'venta',
            'precio_venta' => '2500000.00',
            'municipio' => 'Puebla',
        ]);

        $this->getJson('/api/v1/public/inmuebles?ubicacion=Cuernavaca&tipo_inmueble=combined-match&tipo_operacion=venta&precio_min=2000000&precio_max=3000000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_public_detail_uses_slug_and_returns_ordered_gallery(): void
    {
        $published = $this->property('detalle-publico', true);
        $second = InmuebleImagen::create([
            'inmueble_id' => $published->id,
            'firebase_path' => 'inmuebles/'.$published->id.'/segunda.jpg',
            'url_publica' => 'https://storage.googleapis.com/example/segunda.jpg',
            'nombre_original' => 'segunda.jpg',
            'mime_type' => 'image/jpeg',
            'es_principal' => false,
            'orden' => 1,
            'texto_alternativo' => 'Segunda imagen',
        ]);
        $first = InmuebleImagen::create([
            'inmueble_id' => $published->id,
            'firebase_path' => 'inmuebles/'.$published->id.'/principal.jpg',
            'url_publica' => 'https://storage.googleapis.com/example/principal.jpg',
            'nombre_original' => 'principal.jpg',
            'mime_type' => 'image/jpeg',
            'es_principal' => true,
            'orden' => 0,
            'texto_alternativo' => 'Imagen principal',
        ]);

        $this->getJson('/api/v1/public/inmuebles/'.$published->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $published->slug)
            ->assertJsonPath('data.descripcion', $published->descripcion)
            ->assertJsonPath('data.imagen_principal.url_publica', $first->url_publica)
            ->assertJsonPath('data.imagenes.0.id', $first->id)
            ->assertJsonPath('data.imagenes.1.id', $second->id)
            ->assertJsonMissingPath('data.propietario')
            ->assertJsonMissingPath('data.calle')
            ->assertJsonMissingPath('data.imagenes.0.firebase_path');

        $unpublished = $this->property('detalle-privado', false);

        $this->getJson('/api/v1/public/inmuebles/'.$unpublished->slug)
            ->assertNotFound();
    }

    /** @param array<string, mixed> $overrides */
    private function property(string $suffix, bool $published, array $overrides = []): Inmueble
    {
        $owner = Propietario::create([
            'nombre_razon_social' => 'Propietario '.$suffix,
            'rfc' => strtoupper(substr(md5('owner-'.$suffix), 0, 13)),
            'telefono' => '5555555555',
            'direccion' => 'Dirección '.$suffix,
        ]);
        $category = Categoria::create(['nombre' => 'Categoría '.$suffix]);

        return Inmueble::create(array_merge([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'PUB-'.$suffix,
            'titulo' => 'Inmueble '.$suffix,
            'slug' => 'inmueble-'.$suffix,
            'descripcion' => 'Descripción pública '.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle Principal 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
            'publicado' => $published,
        ], $overrides));
    }
}
