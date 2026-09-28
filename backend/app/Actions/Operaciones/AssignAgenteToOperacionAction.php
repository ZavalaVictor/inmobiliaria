<?php

namespace App\Actions\Operaciones;

use App\Models\Agente;
use App\Models\Operacion;
use App\Models\OperacionAgente;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\Operaciones\OperacionCommissionCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssignAgenteToOperacionAction
{
    public function __construct(
        private readonly OperacionCommissionCalculator $calculator,
        private readonly BitacoraService $bitacora,
    ) {}

    public function execute(Operacion $operacion, array $attributes, ?User $actor = null): OperacionAgente
    {
        try {
            $assignment = DB::transaction(function () use ($operacion, $attributes, $actor): OperacionAgente {
                $lockedOperation = Operacion::query()->whereKey($operacion->getKey())->lockForUpdate()->firstOrFail();
                $agentId = (int) $attributes['agente_id'];

                if (OperacionAgente::query()
                    ->where('operacion_id', $lockedOperation->getKey())
                    ->where('agente_id', $agentId)
                    ->exists()) {
                    throw ValidationException::withMessages(['agente_id' => ['El Agente ya está asociado a esta Operación.']]);
                }

                $agent = Agente::query()->whereKey($agentId)->lockForUpdate()->first();

                if ($agent === null) {
                    throw ValidationException::withMessages(['agente_id' => ['El Agente no existe o no está disponible.']]);
                }

                $hasEligibleAssignments = OperacionAgente::query()
                    ->where('operacion_id', $lockedOperation->getKey())
                    ->whereHas('agente')
                    ->exists();
                $isPrincipal = $hasEligibleAssignments
                    ? (bool) ($attributes['es_principal'] ?? false)
                    : true;

                if ($isPrincipal || ! $hasEligibleAssignments) {
                    OperacionAgente::query()
                        ->where('operacion_id', $lockedOperation->getKey())
                        ->update(['es_principal' => false]);
                }

                $percentage = (string) $agent->porcentaje_comision;
                $assignment = OperacionAgente::create([
                    'operacion_id' => $lockedOperation->getKey(),
                    'agente_id' => $agent->getKey(),
                    'es_principal' => $isPrincipal,
                    'porcentaje_comision' => $percentage,
                    'monto_comision' => $this->calculator->calculate((string) $lockedOperation->monto, $percentage),
                ]);

                $this->bitacora->record($actor, 'operacion_agente_asignado', 'operacion_agente', $assignment->getKey(), $isPrincipal ? 'Agente asignado como principal a Operación.' : 'Agente asignado a Operación.', null, ['operacion_id' => $assignment->operacion_id, 'agente_id' => $assignment->agente_id, 'es_principal' => $assignment->es_principal, 'porcentaje_comision' => $assignment->porcentaje_comision, 'monto_comision' => $assignment->monto_comision]);

                return $assignment;
            });
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'uq_operacion_agente')) {
                throw ValidationException::withMessages(['agente_id' => ['El Agente ya está asociado a esta Operación.']]);
            }

            throw $exception;
        }

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
