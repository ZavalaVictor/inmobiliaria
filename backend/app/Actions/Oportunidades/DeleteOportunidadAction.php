<?php

namespace App\Actions\Oportunidades;

use App\Models\Oportunidad;

final class DeleteOportunidadAction
{
    public function execute(Oportunidad $oportunidad): void
    {
        $oportunidad->delete();
    }
}
