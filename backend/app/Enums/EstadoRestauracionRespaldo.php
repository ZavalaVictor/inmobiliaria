<?php

namespace App\Enums;

enum EstadoRestauracionRespaldo: string
{
    case EnProceso = 'en_proceso';
    case Completada = 'completada';
    case Fallida = 'fallida';
}
