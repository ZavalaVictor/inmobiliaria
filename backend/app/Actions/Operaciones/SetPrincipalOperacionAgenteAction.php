<?php

namespace App\Actions\Operaciones;

use App\Models\Operacion;
use App\Models\OperacionAgente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class SetPrincipalOperacionAgenteAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(Operacion $operacion, OperacionAgente $asignacion, ?User $actor = null): OperacionAgente
    {
        $assignment = DB::transaction(function () use ($operacion, $asignacion, $actor): OperacionAgente {
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

            if ((bool) $target->es_principal) {
                return $target;
            }

            OperacionAgente::query()
                ->where('operacion_id', $lockedOperation->getKey())
                ->update(['es_principal' => false]);
            $target->update(['es_principal' => true]);
            $this->bitacora->record($actor, 'operacion_agente_principal_cambiado', 'operacion_agente', $target->getKey(), 'Principal de Operación cambiado.', null, ['operacion_id' => $target->operacion_id, 'agente_id' => $target->agente_id, 'es_principal' => true, 'porcentaje_comision' => $target->porcentaje_comision, 'monto_comision' => $target->monto_comision]);

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
