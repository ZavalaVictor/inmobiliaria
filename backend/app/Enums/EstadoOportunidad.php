<?php

namespace App\Enums;

enum EstadoOportunidad: string
{
    case Activa = 'activa';
    case Ganada = 'ganada';
    case Perdida = 'perdida';
    case Cancelada = 'cancelada';
}
