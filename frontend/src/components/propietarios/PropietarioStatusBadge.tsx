import type { EstadoRegistroPropietario, TipoPersonaPropietario } from '../../types/propietarios.ts'

const statusStyles: Record<EstadoRegistroPropietario, string> = {
  activo: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
  inactivo: 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200',
}

export function PropietarioStatusBadge({ status }: { status: EstadoRegistroPropietario }): React.JSX.Element {
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${statusStyles[status]}`}>{status === 'activo' ? 'Activo' : 'Inactivo'}</span>
}

export function PropietarioTypeBadge({ type }: { type: TipoPersonaPropietario }): React.JSX.Element {
  return <span className="inline-flex rounded-full bg-[var(--app-accent-soft)] px-2.5 py-1 text-xs font-bold text-[var(--app-accent)]">{type === 'fisica' ? 'Persona física' : 'Persona moral'}</span>
}
