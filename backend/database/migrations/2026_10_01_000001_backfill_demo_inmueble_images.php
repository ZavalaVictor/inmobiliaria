<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reconnect the demo properties with the image objects already stored in Firebase.
     * The insert is idempotent so it is safe to run after a partial deployment.
     */
    public function up(): void
    {
        $bucketUrl = 'https://storage.googleapis.com/sotytech-inmobiliaria.firebasestorage.app/';
        $now = now();

        $images = [
            [1, '0ec49d21-9c98-4a5f-b995-575f4b8287b1', 'Sala luminosa con arcos y madera-1.png', 2897653, true, 0],
            [1, '7d34dea2-2d9f-4950-9980-db520e13c35b', 'Elegante casa familiar en Roma Norte-2.png', 3453307, false, 1],
            [1, 'b6b41c48-a799-444b-9f14-7d63cd198d8c', 'Cocina luminosa y comedor con patio-3.png', 2718087, false, 2],
            [1, 'a788908e-5f4b-4104-b357-aa20c577d223', 'Dormitorio principal con balcón de hierro-4.png', 2751185, false, 3],
            [2, '0e3103e9-263e-4f87-9675-d83fa3ee302b', 'Sala moderna con balcón arbolado en Condesa-1.png', 3279460, true, 0],
            [2, '3070704d-0479-487c-bac3-d642fedbc644', 'Cocina luminosa y comedor en Condesa-2.png', 2684595, false, 1],
            [2, '62ca48ef-d2a8-4744-9e17-8aa3742ed19c', 'Dormitorio moderno con vista arbolada-3.png', 2821877, false, 2],
            [2, 'e6ffc413-5d39-44e9-842b-bc6bfe61d55b', 'Terraza moderna con vista a Condesa-4.png', 3120895, false, 3],
            [3, 'c3bf2c47-8974-42ea-89f8-86e681c1fd35', 'Terreno residencial arbolado en Coyoacán-1.png', 3746285, true, 0],
            [3, '59b32486-ca09-4ad2-a95c-e7072a6f4155', 'Terreno residencial con árboles en Coyoacán-2.png', 3842562, false, 1],
            [3, 'dd20dfcb-09c6-47a1-84d5-b0cca810a365', 'Lote residencial en Coyoacán, vista al portón-3.png', 3539112, false, 2],
            [3, '57f4064f-d5f2-4cab-84f7-36e958f09846', 'Lote residencial abierto en Coyoacán-4.png', 3611724, false, 3],
            [5, 'd8c1a867-a843-4d00-a6ab-02f8c3b90614', 'Oficina equipada con vista a Del Valle-1.png', 2555413, true, 0],
            [5, 'f9cd3fb5-3311-437a-aba0-f4dc84576ba0', 'Oficina ejecutiva con vista arbolada-2.png', 2571601, false, 1],
            [5, 'b655d3b3-4d9e-46ad-a2b9-927170a71acf', 'Sala de juntas contemporánea con vista arbolada-3.png', 2642082, false, 2],
            [5, 'e1681c79-4478-4b57-8b6e-58796ab50e0c', 'Recepción equipada en Del Valle-4.png', 2352489, false, 3],
        ];

        $rows = array_map(static function (array $image) use ($bucketUrl, $now): array {
            [$propertyId, $uuid, $name, $size, $isPrincipal, $order] = $image;
            $path = "inmuebles/{$propertyId}/{$uuid}.png";

            return [
                'inmueble_id' => $propertyId,
                'firebase_path' => $path,
                'url_publica' => $bucketUrl.$path,
                'nombre_original' => $name,
                'mime_type' => 'image/png',
                'tamano_bytes' => $size,
                'es_principal' => $isPrincipal,
                'orden' => $order,
                'texto_alternativo' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $images);

        foreach ($rows as $row) {
            $exists = DB::table('inmueble_imagenes')
                ->where('firebase_path', $row['firebase_path'])
                ->exists();

            if (! $exists) {
                DB::table('inmueble_imagenes')->insert($row);
            }
        }
    }

    public function down(): void
    {
        DB::table('inmueble_imagenes')
            ->whereIn('firebase_path', [
                'inmuebles/1/0ec49d21-9c98-4a5f-b995-575f4b8287b1.png',
                'inmuebles/1/7d34dea2-2d9f-4950-9980-db520e13c35b.png',
                'inmuebles/1/b6b41c48-a799-444b-9f14-7d63cd198d8c.png',
                'inmuebles/1/a788908e-5f4b-4104-b357-aa20c577d223.png',
                'inmuebles/2/0e3103e9-263e-4f87-9675-d83fa3ee302b.png',
                'inmuebles/2/3070704d-0479-487c-bac3-d642fedbc644.png',
                'inmuebles/2/62ca48ef-d2a8-4744-9e17-8aa3742ed19c.png',
                'inmuebles/2/e6ffc413-5d39-44e9-842b-bc6bfe61d55b.png',
                'inmuebles/3/c3bf2c47-8974-42ea-89f8-86e681c1fd35.png',
                'inmuebles/3/59b32486-ca09-4ad2-a95c-e7072a6f4155.png',
                'inmuebles/3/dd20dfcb-09c6-47a1-84d5-b0cca810a365.png',
                'inmuebles/3/57f4064f-d5f2-4cab-84f7-36e958f09846.png',
                'inmuebles/5/d8c1a867-a843-4d00-a6ab-02f8c3b90614.png',
                'inmuebles/5/f9cd3fb5-3311-437a-aba0-f4dc84576ba0.png',
                'inmuebles/5/b655d3b3-4d9e-46ad-a2b9-927170a71acf.png',
                'inmuebles/5/e1681c79-4478-4b57-8b6e-58796ab50e0c.png',
            ])
            ->delete();
    }
};
