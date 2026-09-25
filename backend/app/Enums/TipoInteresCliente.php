<?php

namespace App\Enums;

enum TipoInteresCliente: string
{
    case Compra = 'compra';
    case Renta = 'renta';
    case Ambos = 'ambos';
}
