<?php

namespace App\Actions\InteraccionesClientes;

use App\Models\InteraccionCliente;

final class DeleteInteraccionClienteAction
{
    public function execute(InteraccionCliente $interaccion): void
    {
        $interaccion->delete();
    }
}
