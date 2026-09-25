<?php

namespace App\Enums;

enum EstadoCita: string
{
    case Programada = 'programada';
    case Confirmada = 'confirmada';
    case Completada = 'completada';
    case Cancelada = 'cancelada';
    case NoAsistio = 'no_asistio';
}
