<?php

namespace App\Enums;

enum EtapaOportunidad: string
{
    case ContactoInicial = 'contacto_inicial';
    case Cita = 'cita';
    case Negociacion = 'negociacion';
    case Documentacion = 'documentacion';
    case Cierre = 'cierre';
}
