<?php

namespace App\Actions\Citas;

use App\Models\Cita;
use App\Models\User;

final class UpdateCitaAction
{
    public function execute(User $user, Cita $cita, array $attributes): Cita
    {
        $values = array_intersect_key($attributes, array_flip(['motivo', 'notas']));
        $cita->update($values);

        return $cita->fresh($this->relations());
    }

    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'agente:id,numero_empleado,user_id',
            'agente.user:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'oportunidad:id,titulo,etapa,estado',
            'creadoPor:id,nombres,apellido_paterno,apellido_materno',
        ];
    }
}
