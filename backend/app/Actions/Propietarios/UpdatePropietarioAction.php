<?php

namespace App\Actions\Propietarios;

use App\Models\Propietario;
use Illuminate\Support\Arr;

final class UpdatePropietarioAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Propietario $propietario, array $attributes): Propietario
    {
        $propietario->fill(Arr::only($attributes, [
            'tipo_persona',
            'nombre_razon_social',
            'rfc',
            'telefono',
            'email',
            'direccion',
            'estado_registro',
        ]));
        $propietario->save();

        return $propietario->fresh();
    }
}
