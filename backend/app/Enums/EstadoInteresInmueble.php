<?php

namespace App\Enums;

enum EstadoInteresInmueble: string
{
    case Activo = 'activo';
    case Descartado = 'descartado';
    case Convertido = 'convertido';
}
