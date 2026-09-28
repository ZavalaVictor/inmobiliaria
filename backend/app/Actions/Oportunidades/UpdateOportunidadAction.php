<?php

namespace App\Actions\Oportunidades;

use App\Models\Oportunidad;
use App\Models\User;

final class UpdateOportunidadAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, Oportunidad $oportunidad, array $attributes): Oportunidad
    {
        $values = array_intersect_key($attributes, array_flip([
            'titulo',
            'notas',
            'fecha_cierre',
            'motivo_perdida',
        ]));

        $oportunidad->update($values);

        return $oportunidad->fresh($this->relations());
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'agentePrincipal:id,numero_empleado,user_id',
            'agentePrincipal.user:id,nombres,apellido_paterno,apellido_materno',
            'solicitudInformacion:id,nombre,estado,medio_preferido',
        ];
    }
}
