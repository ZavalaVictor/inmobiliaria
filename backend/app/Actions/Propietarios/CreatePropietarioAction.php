<?php

namespace App\Actions\Propietarios;

use App\Models\Propietario;
use Illuminate\Support\Arr;

final class CreatePropietarioAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Propietario
    {
        return Propietario::create(Arr::only($attributes, [
            'tipo_persona',
            'nombre_razon_social',
            'rfc',
            'telefono',
            'email',
            'direccion',
            'estado_registro',
        ]));
    }
}
