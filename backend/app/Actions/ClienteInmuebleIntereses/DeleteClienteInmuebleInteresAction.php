<?php

namespace App\Actions\ClienteInmuebleIntereses;

use App\Models\ClienteInmuebleInteres;

final class DeleteClienteInmuebleInteresAction
{
    public function execute(ClienteInmuebleInteres $interest): void
    {
        $interest->delete();
    }
}
