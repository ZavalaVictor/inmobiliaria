<?php

namespace App\Actions\AgenteInmuebles;

use App\Models\AgenteInmueble;
use App\Models\Inmueble;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class SetPrincipalAgenteInmuebleAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(Inmueble $inmueble, AgenteInmueble $assignment, ?User $actor = null): AgenteInmueble
    {
        return DB::transaction(function () use ($inmueble, $assignment, $actor): AgenteInmueble {
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

            if ((bool) $target->es_principal) {
                return $target->fresh([
                    'agente:id,user_id,numero_empleado',
                    'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
                ]);
            }

            AgenteInmueble::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->update(['es_principal' => false]);

            AgenteInmueble::query()
                ->whereKey($target->getKey())
                ->update(['es_principal' => true]);

            $this->bitacora->record($actor, 'agente_inmueble_principal_cambiado', 'agente_inmueble', $target->getKey(), 'Principal de Inmueble cambiado.', null, ['agente_id' => $target->agente_id, 'inmueble_id' => $target->inmueble_id, 'es_principal' => true]);

            return $target->fresh([
                'agente:id,user_id,numero_empleado',
                'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
            ]);
        });
    }
}
