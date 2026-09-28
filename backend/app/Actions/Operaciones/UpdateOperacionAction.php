<?php

namespace App\Actions\Operaciones;

use App\Models\Operacion;
use App\Models\User;

final class UpdateOperacionAction
{
    public function execute(User $user, Operacion $operacion, array $attributes): Operacion
    {
        $values = array_intersect_key($attributes, array_flip([
            'estado', 'fecha_inicio_contrato', 'fecha_fin_contrato', 'observaciones',
        ]));
        $operacion->update($values);

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
}
