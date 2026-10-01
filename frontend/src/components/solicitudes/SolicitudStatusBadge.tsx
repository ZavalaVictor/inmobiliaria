import type { EstadoSolicitud } from '../../types/solicitudes.ts'

const statusLabels: Record<EstadoSolicitud, string> = {
  nueva: 'Nueva',
  en_atencion: 'En atención',
  atendida: 'Atendida',
  descartada: 'Descartada',
}

const statusStyles: Record<EstadoSolicitud, string> = {
  nueva: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200',
  en_atencion: 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-200',
  atendida: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
  descartada: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
}

export function SolicitudStatusBadge({ status }: { status: EstadoSolicitud }): React.JSX.Element {
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${statusStyles[status]}`}>{statusLabels[status]}</span>
}
