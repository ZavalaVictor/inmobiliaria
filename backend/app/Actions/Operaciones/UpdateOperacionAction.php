<?php

namespace App\Actions\Operaciones;

use App\Models\Operacion;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;

final class UpdateOperacionAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(User $user, Operacion $operacion, array $attributes): Operacion
    {
        $values = array_intersect_key($attributes, array_flip([
            'estado', 'fecha_inicio_contrato', 'fecha_fin_contrato', 'observaciones',
        ]));
        DB::transaction(function () use ($user, $operacion, $values): void {
            $before = $this->snapshot($operacion);
            $operacion->update($values);
            $this->bitacora->record($user, 'operacion_actualizada', 'operacion', $operacion->getKey(), 'Operación actualizada.', $before, $this->snapshot($operacion));
        });

        return $operacion->fresh($this->relations());
    }

    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'oportunidad:id,titulo,etapa,estado',
            'registradoPor:id,nombres,apellido_paterno,apellido_materno',
            'asignacionesAgentes.agente:id,numero_empleado,user_id',
            'asignacionesAgentes.agente.user:id,nombres,apellido_paterno,apellido_materno',
        ];
    }

    /** @return array<string, mixed> */
    private function snapshot(Operacion $operacion): array
    {
        return [
            'oportunidad_id' => $operacion->oportunidad_id,
            'cliente_id' => $operacion->cliente_id,
            'inmueble_id' => $operacion->inmueble_id,
            'tipo_operacion' => $operacion->tipo_operacion,
            'monto' => $operacion->monto,
            'estado' => $operacion->estado,
            'fecha_operacion' => $operacion->fecha_operacion,
            'fecha_inicio_contrato' => $operacion->fecha_inicio_contrato,
            'fecha_fin_contrato' => $operacion->fecha_fin_contrato,
        ];
    }
}
