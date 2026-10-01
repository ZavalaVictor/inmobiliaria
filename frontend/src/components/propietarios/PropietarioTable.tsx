import { PropietarioStatusBadge, PropietarioTypeBadge } from './PropietarioStatusBadge.tsx'
import type { PropietarioListItem } from '../../types/propietarios.ts'

function initials(owner: PropietarioListItem): string {
  return owner.nombre_razon_social.trim().split(/\s+/).slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('') || 'P'
}

export function PropietarioTable({ owners }: { owners: PropietarioListItem[] }): React.JSX.Element {
  return <div className="hidden overflow-x-auto lg:block">
    <table className="min-w-full text-left text-sm">
      <thead className="border-b border-[var(--app-border)] text-xs uppercase tracking-[0.08em] text-[var(--app-text-muted)]">
        <tr><th className="px-4 py-3 font-bold">Propietario</th><th className="px-4 py-3 font-bold">Contacto</th><th className="px-4 py-3 font-bold">Tipo</th><th className="px-4 py-3 font-bold">RFC</th><th className="px-4 py-3 font-bold">Dirección</th><th className="px-4 py-3 font-bold">Estado</th></tr>
      </thead>
      <tbody className="divide-y divide-[var(--app-border)]">
        {owners.map((owner) => <tr className="align-middle" key={owner.id}>
          <td className="px-4 py-4"><div className="flex items-center gap-3"><span aria-hidden="true" className="grid size-9 shrink-0 place-items-center rounded-full bg-[#e5efff] text-xs font-bold text-[#1f5eb7] dark:bg-blue-950/60 dark:text-blue-200">{initials(owner)}</span><div className="min-w-0"><p className="truncate font-semibold text-[var(--app-text)]">{owner.nombre_razon_social}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">Registrado {owner.created_at ? new Date(owner.created_at).toLocaleDateString('es-MX') : 'sin fecha'}</p></div></div></td>
          <td className="px-4 py-4"><p className="text-[var(--app-text)]">{owner.email || 'Sin email'}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{owner.telefono}</p></td>
          <td className="whitespace-nowrap px-4 py-4"><PropietarioTypeBadge type={owner.tipo_persona} /></td>
          <td className="whitespace-nowrap px-4 py-4 font-medium text-[var(--app-text-muted)]">{owner.rfc}</td>
          <td className="max-w-56 px-4 py-4 text-[var(--app-text-muted)]"><span className="block truncate">{owner.direccion}</span></td>
          <td className="whitespace-nowrap px-4 py-4"><PropietarioStatusBadge status={owner.estado_registro} /></td>
        </tr>)}
      </tbody>
    </table>
  </div>
}
