<?php

namespace App\Actions\Clientes;

use App\Models\Cliente;

final class DeleteClienteAction
{
    public function execute(Cliente $cliente): void
    {
        $cliente->delete();
    }
}
