<?php

namespace Database\Seeders;

use App\Models\Propietario;
use Illuminate\Database\Seeder;

class DemoPropietariosSeeder extends Seeder
{
    /**
     * Create deterministic demo owners without modifying existing records.
     */
    public function run(): void
    {
        $owners = [
            [
                'tipo_persona' => 'fisica',
                'nombre_razon_social' => 'Ana Martínez López',
                'rfc' => 'DEMA010101AAA',
                'telefono' => '5550000001',
                'email' => 'ana.martinez@demo.sotytech.test',
                'direccion' => 'Av. Reforma 101, Cuauhtémoc, Ciudad de México',
            ],
            [
                'tipo_persona' => 'fisica',
                'nombre_razon_social' => 'Carlos Ramírez Torres',
                'rfc' => 'DEMC020202BBB',
                'telefono' => '5550000002',
                'email' => 'carlos.ramirez@demo.sotytech.test',
                'direccion' => 'Calle Durango 202, Roma Norte, Ciudad de México',
            ],
            [
                'tipo_persona' => 'moral',
                'nombre_razon_social' => 'Inversiones SotyTech Demo S.A. de C.V.',
                'rfc' => 'ISD030303CCC',
                'telefono' => '5550000003',
                'email' => 'inversiones@demo.sotytech.test',
                'direccion' => 'Av. Insurgentes Sur 303, Del Valle, Ciudad de México',
            ],
            [
                'tipo_persona' => 'fisica',
                'nombre_razon_social' => 'María Fernanda Ruiz',
                'rfc' => 'DEMR040404DDD',
                'telefono' => '5550000004',
                'email' => 'maria.ruiz@demo.sotytech.test',
                'direccion' => 'Calle Amsterdam 404, Condesa, Ciudad de México',
            ],
            [
                'tipo_persona' => 'moral',
                'nombre_razon_social' => 'Patrimonio Urbano Demo S. de R.L.',
                'rfc' => 'PUD050505EEE',
                'telefono' => '5550000005',
                'email' => 'patrimonio@demo.sotytech.test',
                'direccion' => 'Av. Universidad 505, Santa Cruz Atoyac, Ciudad de México',
            ],
        ];

        foreach ($owners as $owner) {
            Propietario::query()->firstOrCreate(
                ['rfc' => $owner['rfc']],
                [
                    ...$owner,
                    'estado_registro' => 'activo',
                ],
            );
        }
    }
}
