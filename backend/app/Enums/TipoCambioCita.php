<?php

namespace App\Enums;

enum TipoCambioCita: string
{
    case Reprogramacion = 'reprogramacion';
    case CambioAgente = 'cambio_agente';
    case ReprogramacionYAgente = 'reprogramacion_y_agente';
}
