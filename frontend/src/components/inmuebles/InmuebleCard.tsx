import { InmuebleStatusBadge, OperationBadge } from './InmuebleStatusBadge.tsx'
import { InmuebleImageThumbnail } from './InmuebleImageThumbnail.tsx'
import { PropertyPrice } from './PropertyPrice.tsx'
import { navigate } from '../../router/navigation.ts'
import type { InmuebleListItem } from '../../types/inmuebles.ts'

export function InmuebleCard({ property }: { property: InmuebleListItem }): React.JSX.Element {
  return <article className="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-4 shadow-sm">
    <div className="flex items-start justify-between gap-3">
      <div className="flex min-w-0 items-center gap-3"><InmuebleImageThumbnail alt={`Imagen principal de ${property.titulo}`} image={property.imagen_principal} /><div className="min-w-0"><p className="text-xs font-bold uppercase tracking-[0.08em] text-[var(--app-text-muted)]">{property.codigo}</p><a className="mt-1 block truncate font-bold text-[var(--app-text)] hover:text-[var(--app-accent)]" href={`/inmuebles/${property.id}`} onClick={(event) => { if (event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) { event.preventDefault(); navigate(`/inmuebles/${property.id}`) } }}>{property.titulo}</a></div></div>
      <InmuebleStatusBadge status={property.estado_disponibilidad} />
    </div>
    <div className="mt-4 flex flex-wrap items-center gap-2"><OperationBadge operation={property.tipo_operacion} /><PropertyPrice property={property} /></div>
    <dl className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm"><div><dt className="text-[var(--app-text-muted)]">Categoría</dt><dd className="mt-1 font-semibold text-[var(--app-text)]">{property.categoria?.nombre ?? 'Sin categoría'}</dd></div><div><dt className="text-[var(--app-text-muted)]">Ubicación</dt><dd className="mt-1 font-semibold text-[var(--app-text)]">{property.municipio || 'Sin ubicación'}</dd></div><div><dt className="text-[var(--app-text-muted)]">Publicado</dt><dd className="mt-1 font-semibold text-[var(--app-text)]">{property.publicado ? 'Sí' : 'No'}</dd></div></dl>
  </article>
}
