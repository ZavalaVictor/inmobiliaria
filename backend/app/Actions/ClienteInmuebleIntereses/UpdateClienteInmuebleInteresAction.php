<?php

namespace App\Actions\ClienteInmuebleIntereses;

use App\Models\ClienteInmuebleInteres;

final class UpdateClienteInmuebleInteresAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(ClienteInmuebleInteres $interest, array $attributes): ClienteInmuebleInteres
    {
        $interest->update(array_intersect_key($attributes, array_flip([
            'nivel_interes',
            'estado',
            'notas',
        ])));

        return $interest->fresh(['inmueble:id,codigo,titulo,slug']);
    }
}
