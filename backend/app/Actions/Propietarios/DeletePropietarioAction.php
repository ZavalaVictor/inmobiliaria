<?php

namespace App\Actions\Propietarios;

use App\Models\Propietario;

final class DeletePropietarioAction
{
    public function execute(Propietario $propietario): void
    {
        $propietario->delete();
    }
}
