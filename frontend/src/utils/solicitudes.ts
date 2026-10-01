import type { EstadoSolicitud, MedioSolicitud } from '../types/solicitudes.ts'

const statusLabels: Record<EstadoSolicitud, string> = {
  nueva: 'Nueva',
  en_atencion: 'En atención',
  atendida: 'Atendida',
  descartada: 'Descartada',
}

const mediumLabels: Record<MedioSolicitud, string> = {
  telefono: 'Teléfono',
  whatsapp: 'WhatsApp',
  correo: 'Correo',
}

export function getSolicitudStatusLabel(status: EstadoSolicitud): string {
  return statusLabels[status]
}

export function getSolicitudMediumLabel(medium: MedioSolicitud | null): string {
  return medium ? mediumLabels[medium] : 'No especificado'
}
