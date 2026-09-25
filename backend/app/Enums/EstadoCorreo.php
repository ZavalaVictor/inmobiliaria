<?php

namespace App\Enums;

enum EstadoCorreo: string
{
    case Pendiente = 'pendiente';
    case Enviado = 'enviado';
    case Fallido = 'fallido';
}
