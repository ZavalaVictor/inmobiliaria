<?php

namespace App\Enums;

enum EstadoRespaldo: string
{
    case Pendiente = 'pendiente';
    case EnProceso = 'en_proceso';
    case Completado = 'completado';
    case Fallido = 'fallido';
}
