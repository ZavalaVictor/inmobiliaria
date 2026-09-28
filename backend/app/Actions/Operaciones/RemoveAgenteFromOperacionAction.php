<?php

namespace App\Actions\Operaciones;

use App\Models\Operacion;
use App\Models\OperacionAgente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class RemoveAgenteFromOperacionAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(Operacion $operacion, OperacionAgente $asignacion, ?User $actor = null): void
    {
        DB::transaction(function () use ($operacion, $asignacion, $actor): void {
            $lockedOperation = Operacion::query()->whereKey($operacion->getKey())->lockForUpdate()->firstOrFail();
            $target = OperacionAgente::query()
                ->whereKey($asignacion->getKey())
                ->where('operacion_id', $lockedOperation->getKey())
                ->whereHas('agente')
                ->lockForUpdate()
                ->first();

            if ($target === null) {
                throw (new ModelNotFoundException)->setModel(OperacionAgente::class, [$asignacion->getKey()]);
            }

            $wasPrincipal = (bool) $target->es_principal;
            $snapshot = ['operacion_id' => $target->operacion_id, 'agente_id' => $target->agente_id, 'es_principal' => $target->es_principal, 'porcentaje_comision' => $target->porcentaje_comision, 'monto_comision' => $target->monto_comision];
            $target->delete();

            if (! $wasPrincipal) {
                $this->bitacora->record($actor, 'operacion_agente_desasignado', 'operacion_agente', $asignacion->getKey(), 'Agente desasignado de Operación.', $snapshot);

                return;
            }

            $next = OperacionAgente::query()
                ->where('operacion_id', $lockedOperation->getKey())
                ->whereHas('agente')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            OperacionAgente::query()
                ->where('operacion_id', $lockedOperation->getKey())
                ->update(['es_principal' => false]);

            $next?->update(['es_principal' => true]);
            $this->bitacora->record($actor, 'operacion_agente_desasignado', 'operacion_agente', $asignacion->getKey(), $next === null ? 'Agente desasignado de Operación.' : 'Agente desasignado; se promovió otro principal.', $snapshot, $next === null ? null : ['operacion_id' => $next->operacion_id, 'agente_id' => $next->agente_id, 'es_principal' => true, 'porcentaje_comision' => $next->porcentaje_comision, 'monto_comision' => $next->monto_comision]);
        });
    }
}
