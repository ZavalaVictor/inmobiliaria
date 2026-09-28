<?php

namespace App\Actions\Operaciones;

use App\Models\Operacion;
use App\Models\OperacionAgente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class SetPrincipalOperacionAgenteAction
{
    public function execute(Operacion $operacion, OperacionAgente $asignacion): OperacionAgente
    {
        $assignment = DB::transaction(function () use ($operacion, $asignacion): OperacionAgente {
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

            OperacionAgente::query()
                ->where('operacion_id', $lockedOperation->getKey())
                ->update(['es_principal' => false]);
            $target->update(['es_principal' => true]);

            return $target;
        });

        return $assignment->fresh($this->relations());
    }

    private function relations(): array
    {
        return [
            'agente:id,numero_empleado,user_id',
            'agente.user:id,nombres,apellido_paterno,apellido_materno',
        ];
    }
}
