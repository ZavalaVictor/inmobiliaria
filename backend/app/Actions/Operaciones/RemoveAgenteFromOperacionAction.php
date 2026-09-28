<?php

namespace App\Actions\Operaciones;

use App\Models\Operacion;
use App\Models\OperacionAgente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class RemoveAgenteFromOperacionAction
{
    public function execute(Operacion $operacion, OperacionAgente $asignacion): void
    {
        DB::transaction(function () use ($operacion, $asignacion): void {
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
            $target->delete();

            if (! $wasPrincipal) {
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
        });
    }
}
