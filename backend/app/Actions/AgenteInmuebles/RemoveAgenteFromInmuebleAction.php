<?php

namespace App\Actions\AgenteInmuebles;

use App\Models\AgenteInmueble;
use App\Models\Inmueble;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class RemoveAgenteFromInmuebleAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(Inmueble $inmueble, AgenteInmueble $assignment, ?User $actor = null): void
    {
        DB::transaction(function () use ($inmueble, $assignment, $actor): void {
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
            $snapshot = ['agente_id' => $target->agente_id, 'inmueble_id' => $target->inmueble_id, 'es_principal' => $target->es_principal];
            $target->delete();

            $remaining = AgenteInmueble::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->whereHas('agente');
            $remainingCount = (clone $remaining)->count();

            if ($remainingCount === 0) {
                $this->bitacora->record($actor, 'agente_inmueble_desasignado', 'agente_inmueble', $assignment->getKey(), 'Agente desasignado de Inmueble.', $snapshot);

                return;
            }

            $principalCount = (clone $remaining)
                ->where('es_principal', true)
                ->count();

            if (! $wasPrincipal && $principalCount === 1) {
                $this->bitacora->record($actor, 'agente_inmueble_desasignado', 'agente_inmueble', $assignment->getKey(), 'Agente desasignado de Inmueble.', $snapshot);

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

            $this->bitacora->record($actor, 'agente_inmueble_desasignado', 'agente_inmueble', $assignment->getKey(), 'Agente desasignado; se promovió otro principal.', $snapshot, $next === null ? null : ['agente_id' => $next->agente_id, 'inmueble_id' => $next->inmueble_id, 'es_principal' => true]);
        });
    }
}
