<?php

namespace App\Enums;

enum EstadoDisponibilidadInmueble: string
{
    case Disponible = 'disponible';
    case Vendido = 'vendido';
    case Rentado = 'rentado';
    case Inactivo = 'inactivo';
}
