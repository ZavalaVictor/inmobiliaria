<?php

namespace App\Actions\InmuebleImagenes;

use App\Models\Inmueble;
use App\Models\InmuebleImagen;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderInmuebleImagenesAction
{
    /**
     * @param  array<int, int|string>  $orderedIds
     * @return array<int, InmuebleImagen>
     */
    public function execute(Inmueble $inmueble, array $orderedIds): array
    {
        return DB::transaction(function () use ($inmueble, $orderedIds): array {
            $lockedInmueble = Inmueble::query()
                ->whereKey($inmueble->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $activeImages = InmuebleImagen::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get();

            $providedIds = array_map('intval', $orderedIds);
            $expectedIds = $activeImages->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
            sort($providedIds);
            sort($expectedIds);

            if ($providedIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'imagenes' => 'La lista debe contener exactamente todas las imágenes activas del inmueble.',
                ]);
            }

            foreach (array_values(array_map('intval', $orderedIds)) as $order => $imageId) {
                InmuebleImagen::query()
                    ->whereKey($imageId)
                    ->where('inmueble_id', $lockedInmueble->getKey())
                    ->whereNull('deleted_at')
                    ->update([
                        'orden' => $order,
                        'updated_at' => now(),
                    ]);
            }

            return InmuebleImagen::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->orderByDesc('es_principal')
                ->orderBy('orden')
                ->orderBy('id')
                ->get()
                ->all();
        });
    }
}
