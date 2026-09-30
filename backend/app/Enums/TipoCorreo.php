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
    case SolicitudRecibida = 'solicitud_recibida';
    case SolicitudAsignada = 'solicitud_asignada';
    case OportunidadCambioEtapa = 'oportunidad_cambio_etapa';
    case OportunidadCierre = 'oportunidad_cierre';
    case OperacionCreada = 'operacion_creada';
    case OperacionCierre = 'operacion_cierre';
    case DocumentoCargado = 'documento_cargado';
    case PortalClienteActivacion = 'portal_cliente_activacion';
}
