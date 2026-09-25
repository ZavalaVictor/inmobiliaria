<?php

namespace App\Actions\AgenteInmuebles;

use App\Models\AgenteInmueble;
use App\Models\Inmueble;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class RemoveAgenteFromInmuebleAction
{
    public function execute(Inmueble $inmueble, AgenteInmueble $assignment): void
    {
        DB::transaction(function () use ($inmueble, $assignment): void {
            $lockedInmueble = Inmueble::query()
                ->whereKey($inmueble->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $target = AgenteInmueble::query()
                ->whereKey($assignment->getKey())
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->whereHas('agente')
                ->lockForUpdate()
                ->first();

            if ($target === null) {
                throw (new ModelNotFoundException)->setModel(AgenteInmueble::class, [$assignment->getKey()]);
            }

            $wasPrincipal = (bool) $target->es_principal;
            $target->delete();

            $remaining = AgenteInmueble::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->whereHas('agente');
            $remainingCount = (clone $remaining)->count();

            if ($remainingCount === 0) {
                return;
            }

            $principalCount = (clone $remaining)
                ->where('es_principal', true)
                ->count();

            if (! $wasPrincipal && $principalCount === 1) {
                return;
            }

            $next = (clone $remaining)
                ->orderBy('fecha_asignacion')
                ->orderBy('id')
                ->first();

            AgenteInmueble::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->update(['es_principal' => false]);

            if ($next !== null) {
                AgenteInmueble::query()
                    ->whereKey($next->getKey())
                    ->update(['es_principal' => true]);
            }
        });
    }
}
