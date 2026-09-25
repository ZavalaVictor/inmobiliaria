<?php

namespace App\Actions\AgenteInmuebles;

use App\Models\AgenteInmueble;
use App\Models\Inmueble;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class SetPrincipalAgenteInmuebleAction
{
    public function execute(Inmueble $inmueble, AgenteInmueble $assignment): AgenteInmueble
    {
        return DB::transaction(function () use ($inmueble, $assignment): AgenteInmueble {
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

            AgenteInmueble::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->update(['es_principal' => false]);

            AgenteInmueble::query()
                ->whereKey($target->getKey())
                ->update(['es_principal' => true]);

            return $target->fresh([
                'agente:id,user_id,numero_empleado',
                'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
            ]);
        });
    }
}
