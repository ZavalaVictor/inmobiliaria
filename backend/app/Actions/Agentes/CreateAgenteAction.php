<?php

namespace App\Actions\Agentes;

use App\Models\Agente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class CreateAgenteAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, ?User $actor = null): Agente
    {
        $agente = DB::transaction(function () use ($attributes, $actor): Agente {
            $agente = Agente::create(Arr::only($attributes, [
                'user_id',
                'numero_empleado',
                'telefono_corporativo',
                'zona_asignacion',
                'horario',
                'porcentaje_comision',
                'estado_laboral',
                'fecha_contratacion',
            ]));

            $this->bitacora->record($actor, 'agente_creado', 'agente', $agente->getKey(), 'Agente creado.', null, $this->snapshot($agente));

            return $agente;
        });

        return $agente->fresh();
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
