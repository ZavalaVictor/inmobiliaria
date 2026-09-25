<?php

namespace App\Enums;

enum TipoCorreo: string
{
    case ConfirmacionCita = 'confirmacion_cita';
    case ReprogramacionCita = 'reprogramacion_cita';
    case CancelacionCita = 'cancelacion_cita';
    case RecuperacionPassword = 'recuperacion_password';
    case Respaldo = 'respaldo';
    case Restauracion = 'restauracion';
    case Seguridad = 'seguridad';
    case Otro = 'otro';
}
