<?php

namespace App\Enums;

enum TipoEventoOportunidad: string
{
    case Creacion = 'creacion';
    case CambioEtapa = 'cambio_etapa';
    case CambioEstado = 'cambio_estado';
}
