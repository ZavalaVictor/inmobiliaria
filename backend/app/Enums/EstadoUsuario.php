<?php

namespace App\Enums;

enum EstadoUsuario: string
{
    case Pendiente = 'pendiente';
    case Activo = 'activo';
    case Bloqueado = 'bloqueado';
    case Inactivo = 'inactivo';
}
