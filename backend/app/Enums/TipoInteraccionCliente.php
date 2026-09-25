<?php

namespace App\Enums;

enum TipoInteraccionCliente: string
{
    case Llamada = 'llamada';
    case Correo = 'correo';
    case WhatsApp = 'whatsapp';
    case Reunion = 'reunion';
    case Nota = 'nota';
    case Seguimiento = 'seguimiento';
}
