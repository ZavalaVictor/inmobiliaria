<?php

namespace App\Actions\Agentes;

use App\Models\Agente;
use Illuminate\Support\Arr;

final class UpdateAgenteAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Agente $agente, array $attributes): Agente
    {
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

        return $agente->fresh(['user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado']);
    }
}
