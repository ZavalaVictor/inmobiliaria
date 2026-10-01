import { useState } from 'react'
import { PropertyIcon } from '../app/NavIcons.tsx'
import type { PublicInmuebleListItem } from '../../types/public-inmuebles.ts'

function formatPrice(property: PublicInmuebleListItem): string {
  if (property.precio === null) return 'Precio por consultar'

  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: property.moneda,
    maximumFractionDigits: 0,
  }).format(property.precio)
}

function locationLabel(property: PublicInmuebleListItem): string {
  return [property.municipio, property.estado_ubicacion].filter(Boolean).join(', ') || 'Ubicación por confirmar'
}

function PublicPropertyImage({ property }: { property: PublicInmuebleListItem }): React.JSX.Element {
  const [hasError, setHasError] = useState(false)
  const image = property.imagen_principal

  if (image?.url_publica && !hasError) {
    return <img alt={image.texto_alternativo || `Imagen de ${property.titulo}`} className="h-52 w-full object-cover" loading="lazy" onError={() => setHasError(true)} src={image.url_publica} />
  }

  return <div aria-label="Inmueble sin imagen principal" className="flex h-52 w-full flex-col items-center justify-center gap-2 bg-[var(--app-surface-muted)] text-[var(--app-text-muted)]" role="img"><PropertyIcon className="size-12" /><span className="text-sm font-semibold">Imagen próximamente</span></div>
}

export function PublicPropertyCard({ property }: { property: PublicInmuebleListItem }): React.JSX.Element {
  return <article className="overflow-hidden rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] shadow-[0_12px_35px_rgb(15_58_87_/_6%)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_40px_rgb(15_58_87_/_10%)]">
    <PublicPropertyImage property={property} />
    <div className="p-5">
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0"><p className="text-xs font-bold uppercase tracking-[0.1em] text-[var(--app-text-muted)]">{property.codigo}</p><h2 className="mt-1 truncate text-lg font-bold text-[var(--app-text)]">{property.titulo}</h2></div>
        <span className="shrink-0 rounded-full bg-[var(--app-accent-soft)] px-2.5 py-1 text-xs font-bold text-[var(--app-accent)]">{property.tipo_operacion === 'venta' ? 'Venta' : 'Renta'}</span>
      </div>
      <p className="mt-3 text-xl font-black tracking-[-0.03em] text-[var(--app-text)]">{formatPrice(property)}</p>
      <p className="mt-2 text-sm text-[var(--app-text-muted)]">{locationLabel(property)}</p>
      <div className="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-[var(--app-border)] pt-4 text-sm text-[var(--app-text-muted)]"><span>{property.habitaciones ?? 0} habitaciones</span><span>{property.banos_completos ?? 0} baños</span><span>{property.superficie_construccion_m2 ?? property.superficie_terreno_m2 ?? '—'} m²</span></div>
    </div>
  </article>
}
