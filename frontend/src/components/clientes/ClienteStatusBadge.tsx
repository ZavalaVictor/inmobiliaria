import type { EstadoCliente } from '../../types/clientes.ts'
import { getClienteStatusLabel } from '../../utils/clientes.ts'

const classes: Record<EstadoCliente, string> = {
  prospecto: 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200',
  cliente: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200',
  inactivo: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
}

export function ClienteStatusBadge({ status }: { status: EstadoCliente }): React.JSX.Element {
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${classes[status]}`}>{getClienteStatusLabel(status)}</span>
}
