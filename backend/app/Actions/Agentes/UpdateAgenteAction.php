<?php

namespace App\Actions\Agentes;

use App\Models\Agente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateAgenteAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Agente $agente, array $attributes, ?User $actor = null): Agente
    {
        DB::transaction(function () use ($agente, $attributes, $actor): void {
            $before = $this->snapshot($agente);
            $agente->fill(Arr::only($attributes, [
                'numero_empleado',
                'telefono_corporativo',
                'zona_asignacion',
                'horario',
                'porcentaje_comision',
                'estado_laboral',
                'fecha_contratacion',
            ]));
            $agente->save();
            $this->bitacora->record($actor, 'agente_actualizado', 'agente', $agente->getKey(), 'Agente actualizado.', $before, $this->snapshot($agente));
        });

        return $agente->fresh(['user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado']);
    }

    /** @return array<string, mixed> */
    private function snapshot(Agente $agente): array
    {
        return [
            'numero_empleado' => $agente->numero_empleado,
            'telefono_corporativo' => $agente->telefono_corporativo,
            'zona_asignacion' => $agente->zona_asignacion,
            'horario' => $agente->horario,
            'porcentaje_comision' => $agente->porcentaje_comision,
            'estado_laboral' => $agente->estado_laboral,
            'fecha_contratacion' => $agente->fecha_contratacion,
        ];
    }
}
