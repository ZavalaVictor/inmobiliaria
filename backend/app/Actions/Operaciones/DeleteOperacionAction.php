<?php

namespace App\Actions\Operaciones;

use App\Models\Operacion;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteOperacionAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(Operacion $operacion, ?User $actor = null): void
    {
        DB::transaction(function () use ($operacion, $actor): void {
            $locked = Operacion::query()->whereKey($operacion->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->asignacionesAgentes()->exists()) {
                throw ValidationException::withMessages(['operacion' => ['No se puede eliminar una Operación con Agentes asociados.']]);
            }

            if ($locked->documentos()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['operacion' => ['No se puede eliminar una Operación con documentos asociados.']]);
            }

            $before = [
                'oportunidad_id' => $locked->oportunidad_id,
                'cliente_id' => $locked->cliente_id,
                'inmueble_id' => $locked->inmueble_id,
                'tipo_operacion' => $locked->tipo_operacion,
                'monto' => $locked->monto,
                'estado' => $locked->estado,
                'fecha_operacion' => $locked->fecha_operacion,
                'fecha_inicio_contrato' => $locked->fecha_inicio_contrato,
                'fecha_fin_contrato' => $locked->fecha_fin_contrato,
            ];
            $locked->delete();
            $this->bitacora->record($actor, 'operacion_eliminada', 'operacion', $locked->getKey(), 'Operación eliminada.', $before);
        });
    }
}
