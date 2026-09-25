<?php

namespace App\Actions\Agentes;

use App\Models\Agente;

final class DeleteAgenteAction
{
    public function execute(Agente $agente): void
    {
        $agente->delete();
    }
}
