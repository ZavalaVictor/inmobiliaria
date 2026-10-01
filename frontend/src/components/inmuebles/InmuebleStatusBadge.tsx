import type { EstadoDisponibilidadInmueble, TipoOperacion } from '../../types/inmuebles.ts'

const statusLabels: Record<EstadoDisponibilidadInmueble, string> = {
  disponible: 'Disponible',
  vendido: 'Vendido',
  rentado: 'Rentado',
  inactivo: 'Inactivo',
}

const statusStyles: Record<EstadoDisponibilidadInmueble, string> = {
  disponible: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
  vendido: 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-200',
  rentado: 'bg-violet-100 text-violet-800 dark:bg-violet-950/50 dark:text-violet-200',
  inactivo: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
}

export function InmuebleStatusBadge({ status }: { status: EstadoDisponibilidadInmueble }): React.JSX.Element {
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${statusStyles[status]}`}>{statusLabels[status]}</span>
}

export function OperationBadge({ operation }: { operation: TipoOperacion }): React.JSX.Element {
  return <span className="inline-flex rounded-full bg-[var(--app-accent-soft)] px-2.5 py-1 text-xs font-bold text-[var(--app-accent)]">{operation === 'venta' ? 'Venta' : 'Renta'}</span>
}
