<?php

namespace App\Actions\Solicitudes;

use App\Models\SolicitudInformacion;

final class DeleteSolicitudInformacionAction
{
    public function execute(SolicitudInformacion $solicitud): void
    {
        $solicitud->delete();
    }
}
