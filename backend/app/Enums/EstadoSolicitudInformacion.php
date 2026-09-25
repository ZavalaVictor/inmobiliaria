<?php

namespace App\Enums;

enum EstadoSolicitudInformacion: string
{
    case Nueva = 'nueva';
    case EnAtencion = 'en_atencion';
    case Atendida = 'atendida';
    case Descartada = 'descartada';
}
