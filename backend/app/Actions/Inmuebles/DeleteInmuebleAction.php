<?php

namespace App\Actions\Inmuebles;

use App\Models\Inmueble;

final class DeleteInmuebleAction
{
    public function execute(Inmueble $inmueble): void
    {
        $inmueble->delete();
    }
}
