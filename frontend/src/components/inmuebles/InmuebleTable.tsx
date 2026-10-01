import { InmuebleStatusBadge, OperationBadge } from './InmuebleStatusBadge.tsx'
import { InmuebleImageThumbnail } from './InmuebleImageThumbnail.tsx'
import { PropertyPrice } from './PropertyPrice.tsx'
import { navigate } from '../../router/navigation.ts'
import type { InmuebleListItem } from '../../types/inmuebles.ts'

function locationLabel(property: InmuebleListItem): string {
  return [property.municipio, property.estado_ubicacion].filter(Boolean).join(', ') || 'Sin ubicación'
}

export function InmuebleTable({ properties }: { properties: InmuebleListItem[] }): React.JSX.Element {
  return <div className="hidden overflow-x-auto lg:block">
    <table className="min-w-full text-left text-sm">
      <thead className="border-b border-[var(--app-border)] text-xs uppercase tracking-[0.08em] text-[var(--app-text-muted)]">
        <tr>
          <th className="px-4 py-3 font-bold">Código</th>
          <th className="px-4 py-3 font-bold">Inmueble</th>
          <th className="px-4 py-3 font-bold">Operación</th>
          <th className="px-4 py-3 font-bold">Precio</th>
          <th className="px-4 py-3 font-bold">Categoría</th>
          <th className="px-4 py-3 font-bold">Ubicación</th>
          <th className="px-4 py-3 font-bold">Estado</th>
          <th className="px-4 py-3 font-bold">Publicado</th>
        </tr>
      </thead>
      <tbody className="divide-y divide-[var(--app-border)]">
        {properties.map((property) => <tr className="align-middle" key={property.id}>
          <td className="whitespace-nowrap px-4 py-4 font-semibold text-[var(--app-text-muted)]">{property.codigo}</td>
          <td className="max-w-72 px-4 py-4"><div className="flex min-w-56 items-center gap-3"><InmuebleImageThumbnail alt={`Imagen principal de ${property.titulo}`} image={property.imagen_principal} /><div className="min-w-0"><a className="block truncate font-semibold text-[var(--app-text)] hover:text-[var(--app-accent)]" href={`/inmuebles/${property.id}`} onClick={(event) => { if (event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) { event.preventDefault(); navigate(`/inmuebles/${property.id}`) } }}>{property.titulo}</a><p className="mt-1 truncate text-xs text-[var(--app-text-muted)]">{property.descripcion || 'Sin descripción'}</p></div></div></td>
          <td className="whitespace-nowrap px-4 py-4"><OperationBadge operation={property.tipo_operacion} /></td>
          <td className="whitespace-nowrap px-4 py-4"><PropertyPrice property={property} /></td>
          <td className="whitespace-nowrap px-4 py-4 text-[var(--app-text-muted)]">{property.categoria?.nombre ?? 'Sin categoría'}</td>
          <td className="max-w-40 px-4 py-4 text-[var(--app-text-muted)]"><span className="block truncate">{locationLabel(property)}</span></td>
          <td className="whitespace-nowrap px-4 py-4"><InmuebleStatusBadge status={property.estado_disponibilidad} /></td>
          <td className="whitespace-nowrap px-4 py-4 text-xs font-semibold text-[var(--app-text-muted)]">{property.publicado ? 'Sí' : 'No'}</td>
        </tr>)}
      </tbody>
    </table>
  </div>
}
