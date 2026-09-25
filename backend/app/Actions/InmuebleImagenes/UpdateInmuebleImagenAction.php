<?php

namespace App\Actions\InmuebleImagenes;

use App\Models\InmuebleImagen;

class UpdateInmuebleImagenAction
{
    /** @param array<string, mixed> $attributes */
    public function execute(InmuebleImagen $imagen, array $attributes): InmuebleImagen
    {
        if (array_key_exists('texto_alternativo', $attributes)) {
            $imagen->texto_alternativo = $attributes['texto_alternativo'];
        }

        $imagen->save();

        return $imagen->fresh();
    }
}
