<?php

namespace App\Actions\InmuebleImagenes;

use App\Models\Inmueble;
use App\Models\InmuebleImagen;
use Illuminate\Support\Facades\DB;

class SetPrincipalInmuebleImagenAction
{
    public function execute(Inmueble $inmueble, InmuebleImagen $imagen): InmuebleImagen
    {
        return DB::transaction(function () use ($inmueble, $imagen): InmuebleImagen {
            $lockedInmueble = Inmueble::query()
                ->whereKey($inmueble->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $target = InmuebleImagen::query()
                ->whereKey($imagen->getKey())
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            InmuebleImagen::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->whereNull('deleted_at')
                ->whereKeyNot($target->getKey())
                ->update(['es_principal' => false]);

            $target->update(['es_principal' => true]);

            return $target->fresh();
        });
    }
}
