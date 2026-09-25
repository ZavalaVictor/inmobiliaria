<?php

namespace App\Enums;

enum MedioSolicitudInformacion: string
{
    case Telefono = 'telefono';
    case WhatsApp = 'whatsapp';
    case Correo = 'correo';
}
