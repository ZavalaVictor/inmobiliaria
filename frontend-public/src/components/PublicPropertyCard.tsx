import { useState } from 'react'
import { navigate } from '../navigation.ts'
import type { PublicProperty } from '../types.ts'

function formatPrice(property: PublicProperty): string {
  if (property.precio === null) return 'Precio por consultar'
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency: property.moneda, maximumFractionDigits: 0 }).format(property.precio)
}

function PropertyImage({ property, large = false }: { property: PublicProperty; large?: boolean }): React.JSX.Element {
  const [failed, setFailed] = useState(false)
  const image = property.imagen_principal
  if (image?.url_publica && !failed) return <img alt={image.texto_alternativo || `Imagen de ${property.titulo}`} className={`${large ? 'h-72 sm:h-96' : 'h-56'} w-full object-cover`} loading="lazy" onError={() => setFailed(true)} src={image.url_publica} />
  return <div aria-label="Sin imagen principal" className={`${large ? 'h-72 sm:h-96' : 'h-56'} public-image-placeholder`} role="img"><svg aria-hidden="true" className="size-14" fill="none" viewBox="0 0 24 24"><path d="m3.5 10 8.5-7 8.5 7v9.5a1.5 1.5 0 0 1-1.5 1.5H5a1.5 1.5 0 0 1-1.5-1.5V10Z" stroke="currentColor" strokeLinejoin="round" strokeWidth="1.5" /><path d="M9 21v-6h6v6" stroke="currentColor" strokeWidth="1.5" /></svg><span className="mt-2 text-sm font-semibold">Imagen próximamente</span></div>
}

export function PublicPropertyCard({ property }: { property: PublicProperty }): React.JSX.Element {
  return <article className="public-card group overflow-hidden"><button aria-label={`Ver detalle de ${property.titulo}`} className="block w-full text-left" onClick={() => navigate(`/propiedades/${property.slug}`)} type="button"><PropertyImage property={property} /><div className="p-5"><div className="flex items-start justify-between gap-3"><div className="min-w-0"><p className="public-kicker">{property.codigo}</p><h2 className="public-display mt-1 truncate text-2xl font-bold text-[var(--public-text)]">{property.titulo}</h2></div><span className="public-operation-badge">{property.tipo_operacion === 'venta' ? 'Venta' : 'Renta'}</span></div><p className="mt-4 text-2xl font-black tracking-[-0.04em] text-[var(--public-text)]">{formatPrice(property)}</p><p className="mt-2 text-sm text-[var(--public-muted)]">{[property.municipio, property.estado_ubicacion].filter(Boolean).join(', ') || 'Ubicación por confirmar'}</p><div className="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-[var(--public-border)] pt-4 text-sm text-[var(--public-muted)]"><span>{property.habitaciones ?? 0} habitaciones</span><span>{property.banos_completos ?? 0} baños</span><span>{property.estacionamientos ?? 0} estacionamientos</span><span>{property.superficie_construccion_m2 ?? property.superficie_terreno_m2 ?? '—'} m²</span></div></div></button></article>
}

export { PropertyImage, formatPrice }
