<?php

namespace App\Services\Solicitudes;

use App\Models\SolicitudInformacion;
use App\Notifications\Solicitudes\SolicitudAsignadaNotification;

final class SolicitudNotificationDispatcher
{
    public function assigned(SolicitudInformacion $solicitud): void
    {
        $responsable = $solicitud->atendidaPor;

        if ($responsable !== null) {
            $responsable->notify(new SolicitudAsignadaNotification($solicitud));
        }
    }
}
