<?php

namespace App\Actions\Agentes;

use App\Models\Agente;
use Illuminate\Support\Arr;

final class CreateAgenteAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Agente
    {
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

        return $agente->fresh();
    }
}
