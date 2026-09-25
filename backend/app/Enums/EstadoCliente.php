<?php

namespace App\Enums;

enum EstadoCliente: string
{
    case Prospecto = 'prospecto';
    case Cliente = 'cliente';
    case Inactivo = 'inactivo';
}
