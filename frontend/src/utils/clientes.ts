import type { EstadoCliente, EstadoInteres, NivelInteres, TipoInteraccion, TipoInteresCliente } from '../types/clientes.ts'

export function getClienteStatusLabel(value: EstadoCliente): string {
  return { prospecto: 'Prospecto', cliente: 'Cliente', inactivo: 'Inactivo' }[value]
}
export function getClienteInterestTypeLabel(value: TipoInteresCliente | null): string {
  if (!value) return 'Sin definir'
  return { compra: 'Compra', renta: 'Renta', ambos: 'Compra y renta' }[value]
}

export function getInterestLevelLabel(value: NivelInteres | null): string {
  if (!value) return 'Sin definir'
  return { bajo: 'Bajo', medio: 'Medio', alto: 'Alto' }[value]
}

export function getInterestStatusLabel(value: EstadoInteres): string {
  return { activo: 'Activo', descartado: 'Descartado', convertido: 'Convertido' }[value]
}

export function getInteractionTypeLabel(value: TipoInteraccion): string {
  return { llamada: 'Llamada', correo: 'Correo', whatsapp: 'WhatsApp', reunion: 'Reunión', nota: 'Nota', seguimiento: 'Seguimiento' }[value]
}

export function getFullClientName(client: { nombres: string; apellido_paterno: string; apellido_materno?: string | null }): string {
  return [client.nombres, client.apellido_paterno, client.apellido_materno].filter(Boolean).join(' ')
}

export function formatClientDate(value: string | null): string {
  if (!value) return '—'
  return new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}
