<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class DemoCategoriasSeeder extends Seeder
{
    /**
     * Create deterministic demo categories without modifying existing records.
     */
    public function run(): void
    {
        $categories = [
            [
                'nombre' => 'Casa',
                'descripcion' => 'Casas residenciales y familiares.',
            ],
            [
                'nombre' => 'Departamento',
                'descripcion' => 'Departamentos y unidades habitacionales.',
            ],
            [
                'nombre' => 'Terreno',
                'descripcion' => 'Terrenos residenciales, comerciales o de inversión.',
            ],
            [
                'nombre' => 'Local comercial',
                'descripcion' => 'Espacios destinados a actividades comerciales.',
            ],
            [
                'nombre' => 'Oficina',
                'descripcion' => 'Espacios destinados a actividades profesionales y administrativas.',
            ],
        ];

        foreach ($categories as $category) {
            Categoria::query()->firstOrCreate(
                ['nombre' => $category['nombre']],
                [
                    ...$category,
                    'activo' => true,
                ],
            );
        }
    }
}
