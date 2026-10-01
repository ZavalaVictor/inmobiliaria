<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Inmueble;
use App\Models\Propietario;
use Illuminate\Database\Seeder;

class DemoInmueblesSeeder extends Seeder
{
    /**
     * Create deterministic demo properties without modifying existing records.
     */
    public function run(): void
    {
        $owners = Propietario::query()
            ->whereIn('rfc', [
                'DEMA010101AAA',
                'DEMC020202BBB',
                'ISD030303CCC',
                'DEMR040404DDD',
                'PUD050505EEE',
            ])
            ->get()
            ->keyBy('rfc');

        $categories = Categoria::query()
            ->whereIn('nombre', ['Casa', 'Departamento', 'Terreno', 'Local comercial', 'Oficina'])
            ->get()
            ->keyBy('nombre');

        $properties = [
            [
                'owner_rfc' => 'DEMA010101AAA',
                'category' => 'Casa',
                'codigo' => 'DEMO-CASA-001',
                'titulo' => 'Casa familiar en Roma Norte',
                'slug' => 'casa-familiar-roma-norte',
                'descripcion' => 'Casa residencial con espacios amplios y buena conectividad.',
                'tipo_operacion' => 'venta',
                'precio_venta' => '4850000.00',
                'superficie_terreno_m2' => '180.00',
                'superficie_construccion_m2' => '220.00',
                'habitaciones' => 3,
                'banos_completos' => 2,
                'medios_banos' => 1,
                'estacionamientos' => 2,
                'niveles' => 2,
                'calle' => 'Calle Orizaba',
                'numero_exterior' => '101',
                'colonia' => 'Roma Norte',
                'municipio' => 'Cuauhtémoc',
                'estado_ubicacion' => 'Ciudad de México',
                'codigo_postal' => '06700',
                'referencias' => 'A dos calles de Álvaro Obregón.',
                'latitud' => '19.4175000',
                'longitud' => '-99.1622000',
                'estado_disponibilidad' => 'disponible',
                'publicado' => true,
                'fecha_publicacion' => '2026-09-15 10:00:00',
            ],
            [
                'owner_rfc' => 'DEMC020202BBB',
                'category' => 'Departamento',
                'codigo' => 'DEMO-DEPTO-001',
                'titulo' => 'Departamento moderno en Condesa',
                'slug' => 'departamento-moderno-condesa',
                'descripcion' => 'Departamento funcional con iluminación natural y amenidades.',
                'tipo_operacion' => 'renta',
                'renta_mensual' => '28500.00',
                'superficie_terreno_m2' => '95.00',
                'superficie_construccion_m2' => '95.00',
                'habitaciones' => 2,
                'banos_completos' => 2,
                'medios_banos' => 0,
                'estacionamientos' => 1,
                'niveles' => 1,
                'calle' => 'Calle Amsterdam',
                'numero_exterior' => '220',
                'numero_interior' => '4B',
                'colonia' => 'Condesa',
                'municipio' => 'Cuauhtémoc',
                'estado_ubicacion' => 'Ciudad de México',
                'codigo_postal' => '06140',
                'referencias' => 'Edificio con vigilancia y roof garden común.',
                'latitud' => '19.4117000',
                'longitud' => '-99.1700000',
                'estado_disponibilidad' => 'disponible',
                'publicado' => true,
                'fecha_publicacion' => '2026-09-18 12:30:00',
            ],
            [
                'owner_rfc' => 'ISD030303CCC',
                'category' => 'Terreno',
                'codigo' => 'DEMO-TERRENO-001',
                'titulo' => 'Terreno residencial en Coyoacán',
                'slug' => 'terreno-residencial-coyoacan',
                'descripcion' => 'Terreno con potencial para desarrollo residencial.',
                'tipo_operacion' => 'venta',
                'precio_venta' => '3200000.00',
                'superficie_terreno_m2' => '300.00',
                'superficie_construccion_m2' => '0.00',
                'habitaciones' => 0,
                'banos_completos' => 0,
                'medios_banos' => 0,
                'estacionamientos' => 0,
                'niveles' => 1,
                'calle' => 'Calle Francisco Sosa',
                'numero_exterior' => '45',
                'colonia' => 'Santa Catarina',
                'municipio' => 'Coyoacán',
                'estado_ubicacion' => 'Ciudad de México',
                'codigo_postal' => '04010',
                'referencias' => 'Cerca del centro de Coyoacán.',
                'latitud' => '19.3508000',
                'longitud' => '-99.1625000',
                'estado_disponibilidad' => 'disponible',
                'publicado' => true,
                'fecha_publicacion' => '2026-09-20 09:15:00',
            ],
            [
                'owner_rfc' => 'DEMR040404DDD',
                'category' => 'Local comercial',
                'codigo' => 'DEMO-LOCAL-001',
                'titulo' => 'Local comercial sobre Insurgentes',
                'slug' => 'local-comercial-insurgentes',
                'descripcion' => 'Local con ubicación estratégica para comercio y servicios.',
                'tipo_operacion' => 'renta',
                'renta_mensual' => '42000.00',
                'superficie_terreno_m2' => '120.00',
                'superficie_construccion_m2' => '120.00',
                'habitaciones' => 0,
                'banos_completos' => 1,
                'medios_banos' => 0,
                'estacionamientos' => 2,
                'niveles' => 1,
                'calle' => 'Avenida Insurgentes Sur',
                'numero_exterior' => '880',
                'colonia' => 'Del Valle Centro',
                'municipio' => 'Benito Juárez',
                'estado_ubicacion' => 'Ciudad de México',
                'codigo_postal' => '03100',
                'referencias' => 'Frente a estación de transporte público.',
                'latitud' => '19.3758000',
                'longitud' => '-99.1726000',
                'estado_disponibilidad' => 'rentado',
                'publicado' => false,
                'fecha_publicacion' => null,
            ],
            [
                'owner_rfc' => 'PUD050505EEE',
                'category' => 'Oficina',
                'codigo' => 'DEMO-OFICINA-001',
                'titulo' => 'Oficina equipada en Del Valle',
                'slug' => 'oficina-equipada-del-valle',
                'descripcion' => 'Oficina lista para operar con espacios privados y área de recepción.',
                'tipo_operacion' => 'renta',
                'renta_mensual' => '18000.00',
                'superficie_terreno_m2' => '70.00',
                'superficie_construccion_m2' => '70.00',
                'habitaciones' => 0,
                'banos_completos' => 1,
                'medios_banos' => 0,
                'estacionamientos' => 1,
                'niveles' => 1,
                'calle' => 'Calle Parroquia',
                'numero_exterior' => '315',
                'numero_interior' => '302',
                'colonia' => 'Del Valle Norte',
                'municipio' => 'Benito Juárez',
                'estado_ubicacion' => 'Ciudad de México',
                'codigo_postal' => '03103',
                'referencias' => 'Edificio corporativo con elevador.',
                'latitud' => '19.3813000',
                'longitud' => '-99.1645000',
                'estado_disponibilidad' => 'disponible',
                'publicado' => true,
                'fecha_publicacion' => '2026-09-22 14:00:00',
            ],
        ];

        foreach ($properties as $property) {
            $owner = $owners->get($property['owner_rfc']);
            $category = $categories->get($property['category']);

            if ($owner === null || $category === null) {
                throw new \RuntimeException('Los propietarios y categorías demo deben existir antes de sembrar inmuebles.');
            }

            unset($property['owner_rfc'], $property['category']);

            Inmueble::query()->firstOrCreate(
                ['codigo' => $property['codigo']],
                [
                    ...$property,
                    'propietario_id' => $owner->id,
                    'categoria_id' => $category->id,
                ],
            );
        }
    }
}
