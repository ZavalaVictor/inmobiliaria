import { PropietarioStatusBadge, PropietarioTypeBadge } from './PropietarioStatusBadge.tsx'
import type { PropietarioListItem } from '../../types/propietarios.ts'

export function PropietarioCard({ owner }: { owner: PropietarioListItem }): React.JSX.Element {
  return <article className="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-4 shadow-sm">
    <div className="flex items-start justify-between gap-3"><div className="min-w-0"><p className="truncate font-bold text-[var(--app-text)]">{owner.nombre_razon_social}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{owner.rfc}</p></div><PropietarioStatusBadge status={owner.estado_registro} /></div>
    <div className="mt-4 flex flex-wrap gap-2"><PropietarioTypeBadge type={owner.tipo_persona} /></div>
    <dl className="mt-4 space-y-3 text-sm"><div><dt className="text-[var(--app-text-muted)]">Contacto</dt><dd className="mt-1 font-semibold text-[var(--app-text)]">{owner.email || 'Sin email'} · {owner.telefono}</dd></div><div><dt className="text-[var(--app-text-muted)]">Dirección</dt><dd className="mt-1 font-semibold text-[var(--app-text)]">{owner.direccion}</dd></div></dl>
  </article>
}
