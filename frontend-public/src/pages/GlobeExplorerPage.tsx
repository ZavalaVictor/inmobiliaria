import { useEffect, useMemo, useState } from 'react'
import { PropertyMap, type PropertyMapPoint } from '../components/PropertyMap.tsx'
import { PublicNavbar } from '../components/PublicNavbar.tsx'
import { EmptyState, ErrorState, LoadingState } from '../components/PublicStates.tsx'
import { PublicApiError } from '../http.ts'
import { navigate } from '../navigation.ts'
import { listProperties } from '../services.ts'
import type { PublicFilters, PublicProperty } from '../types.ts'

const DEFAULT_FILTERS: PublicFilters = {
  q: '',
  ubicacion: '',
  tipo_inmueble: '',
  tipo_operacion: '',
  precio_min: '',
  precio_max: '',
  page: 1,
  per_page: 50,
  sort: 'created_at',
  direction: 'desc',
}

type FilterField = 'q' | 'ubicacion' | 'tipo_inmueble' | 'tipo_operacion' | 'precio_min' | 'precio_max'

function readFilters(): PublicFilters {
  const params = new URLSearchParams(window.location.search)
  const operation = params.get('tipo_operacion')

  return {
    ...DEFAULT_FILTERS,
    q: params.get('q') ?? '',
    ubicacion: params.get('ubicacion') ?? '',
    tipo_inmueble: params.get('tipo_inmueble') ?? '',
    tipo_operacion: operation === 'venta' || operation === 'renta' ? operation : '',
    precio_min: params.get('precio_min') ?? '',
    precio_max: params.get('precio_max') ?? '',
  }
}

function updateUrl(filters: PublicFilters): void {
  const params = new URLSearchParams()

  for (const [key, value] of Object.entries(filters)) {
    if (key === 'page' || key === 'per_page' || key === 'sort' || key === 'direction') continue
    if (value !== '') params.set(key, String(value))
  }

  const query = params.toString()
  window.history.replaceState({}, '', `/explorar${query ? `?${query}` : ''}`)
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

function formatPrice(property: PublicProperty): string {
  if (property.precio === null) return 'Precio por consultar'

  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: property.moneda,
    maximumFractionDigits: 0,
  }).format(property.precio)
}

function toMapPoints(properties: PublicProperty[]): PropertyMapPoint[] {
  return properties.flatMap((property) => {
    if (!property.coordenadas) return []

    return [{
      id: property.id,
      latitud: property.coordenadas.latitud,
      longitud: property.coordenadas.longitud,
      label: property.titulo,
      price: formatPrice(property),
      imageUrl: property.imagen_principal?.url_publica ?? null,
      detailUrl: `/propiedades/${encodeURIComponent(property.slug)}`,
    }]
  })
}

function PropertyPreview({ property, onSelect, selected }: { property: PublicProperty; onSelect: () => void; selected: boolean }): React.JSX.Element {
  const image = property.imagen_principal

  return <button aria-pressed={selected} className={`globe-property-result ${selected ? 'globe-property-result-selected' : ''}`} onClick={onSelect} type="button">
    {image?.url_publica ? <img alt="" className="globe-property-result-image" decoding="async" loading="lazy" src={image.url_publica} /> : <span aria-hidden="true" className="globe-property-result-placeholder">⌂</span>}
    <span className="globe-property-result-content"><span className="globe-property-result-code">{property.codigo}</span><strong>{property.titulo}</strong><span>{[property.municipio, property.estado_ubicacion].filter(Boolean).join(', ') || 'Ubicación por confirmar'}</span><b>{formatPrice(property)}</b></span>
  </button>
}

export function GlobeExplorerPage({ theme, onToggleTheme }: { theme: 'light' | 'dark'; onToggleTheme: () => void }): React.JSX.Element {
  const [filters, setFilters] = useState<PublicFilters>(readFilters)
  const [properties, setProperties] = useState<PublicProperty[]>([])
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [filtersOpen, setFiltersOpen] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<unknown>(null)

  useEffect(() => {
    const controller = new AbortController()
    const timeout = window.setTimeout(() => {
      updateUrl(filters)
      setLoading(true)
      setError(null)

      listProperties(filters, controller.signal)
        .then((response) => {
          setProperties(response.data)
          setSelectedId((current) => response.data.some((property) => property.id === current) ? current : null)
        })
        .catch((nextError: unknown) => {
          if (!isAbortError(nextError)) setError(nextError)
        })
        .finally(() => {
          if (!controller.signal.aborted) setLoading(false)
        })
    }, filters.q ? 350 : 0)

    return () => {
      window.clearTimeout(timeout)
      controller.abort()
    }
  }, [filters])

  useEffect(() => {
    const onPopState = (): void => setFilters(readFilters())
    window.addEventListener('popstate', onPopState)
    return () => window.removeEventListener('popstate', onPopState)
  }, [])

  const updateFilter = (field: FilterField, value: string): void => {
    setFilters((current) => ({ ...current, [field]: value, page: 1 }))
  }

  const clearFilters = (): void => {
    setFilters(DEFAULT_FILTERS)
    navigate('/explorar')
  }

  const points = useMemo(() => toMapPoints(properties), [properties])
  const mappedProperties = useMemo(() => properties.filter((property) => property.coordenadas), [properties])
  const selectedProperty = properties.find((property) => property.id === selectedId) ?? null
  const hasFilters = Boolean(filters.q || filters.ubicacion || filters.tipo_inmueble || filters.tipo_operacion || filters.precio_min || filters.precio_max)

  const selectProperty = (id: number): void => {
    setSelectedId(id)
    setFiltersOpen(false)
  }

  return <div className="public-page globe-explorer-page">
    <PublicNavbar onToggleTheme={onToggleTheme} theme={theme} />
    <main>
      <section className="public-container globe-explorer-layout" aria-labelledby="globe-heading">
        <aside className="globe-explorer-panel public-card p-5 sm:p-6">
          <div className="flex items-start justify-between gap-3"><div><p className="public-kicker text-[var(--public-accent)]">Explora por ubicación</p><h1 className="public-display mt-2 text-3xl font-bold text-[var(--public-text)]" id="globe-heading">Encuentra tu próximo espacio</h1></div><button aria-label="Limpiar filtros" className="globe-clear-button" onClick={clearFilters} type="button">Limpiar</button></div>
          <button aria-controls="globe-filters" aria-expanded={filtersOpen} className="globe-filter-toggle" onClick={() => setFiltersOpen((open) => !open)} type="button"><span>Buscar y filtrar</span><span aria-hidden="true">{filtersOpen ? '−' : '+'}</span></button>
          <div className={`globe-filter-content ${filtersOpen ? 'globe-filter-content-open' : ''}`} id="globe-filters">
            <div className="globe-filter-stack mt-6">
            <label className="public-label">Buscar<input aria-label="Buscar propiedades" className="public-input mt-2" onChange={(event) => updateFilter('q', event.target.value)} placeholder="Título, código o descripción" type="search" value={filters.q} /></label>
            <label className="public-label">Ubicación<input aria-label="Filtrar por ubicación" className="public-input mt-2" onChange={(event) => updateFilter('ubicacion', event.target.value)} placeholder="Colonia, ciudad o estado" value={filters.ubicacion} /></label>
            <div className="grid grid-cols-2 gap-3"><label className="public-label">Operación<select className="public-input mt-2" onChange={(event) => updateFilter('tipo_operacion', event.target.value)} value={filters.tipo_operacion}><option value="">Todas</option><option value="venta">Venta</option><option value="renta">Renta</option></select></label><label className="public-label">Tipo<input aria-label="Filtrar por tipo" className="public-input mt-2" onChange={(event) => updateFilter('tipo_inmueble', event.target.value)} placeholder="Casa" value={filters.tipo_inmueble} /></label></div>
            <div className="grid grid-cols-2 gap-3"><label className="public-label">Precio mínimo<input aria-label="Precio mínimo" className="public-input mt-2" inputMode="numeric" min="0" onChange={(event) => updateFilter('precio_min', event.target.value)} placeholder="$ 0" type="number" value={filters.precio_min} /></label><label className="public-label">Precio máximo<input aria-label="Precio máximo" className="public-input mt-2" inputMode="numeric" min="0" onChange={(event) => updateFilter('precio_max', event.target.value)} placeholder="Sin límite" type="number" value={filters.precio_max} /></label></div>
            </div>
          </div>
          <div aria-live="polite" className="globe-results-heading"><strong>{mappedProperties.length}</strong><span>{hasFilters ? 'resultados con ubicación' : 'propiedades con ubicación'}</span></div>
          {loading && properties.length === 0 ? <LoadingState /> : null}
          {error ? <div className="mt-5"><ErrorState detail={error instanceof PublicApiError && error.status >= 500 ? 'El catálogo público no respondió correctamente.' : undefined} onRetry={() => setFilters((current) => ({ ...current }))} /></div> : null}
          {!loading && !error && mappedProperties.length === 0 ? <div className="mt-5"><EmptyState filtered={hasFilters} /></div> : null}
          {mappedProperties.length > 0 ? <div aria-label="Resultados de propiedades" className="globe-property-results">{mappedProperties.map((property) => <PropertyPreview key={property.id} onSelect={() => selectProperty(property.id)} property={property} selected={selectedId === property.id} />)}</div> : null}
        </aside>

        <div className="globe-explorer-stage public-card">
          {loading && properties.length === 0 ? <LoadingState /> : null}
          {!loading && !error ? <PropertyMap onPointSelect={selectProperty} points={points} selectedPointId={selectedId} /> : null}
          {selectedProperty ? <div className="globe-selected-card"><div className="globe-selected-card-image">{selectedProperty.imagen_principal?.url_publica ? <img alt={selectedProperty.imagen_principal.texto_alternativo || `Imagen de ${selectedProperty.titulo}`} decoding="async" src={selectedProperty.imagen_principal.url_publica} /> : <span>Sin imagen</span>}</div><div className="min-w-0"><p className="globe-property-result-code">{selectedProperty.codigo}</p><strong>{selectedProperty.titulo}</strong><span>{[selectedProperty.municipio, selectedProperty.estado_ubicacion].filter(Boolean).join(', ')}</span><b>{formatPrice(selectedProperty)}</b></div><button aria-label={`Ver detalle de ${selectedProperty.titulo}`} className="globe-selected-card-link" onClick={() => navigate(`/propiedades/${selectedProperty.slug}`)} type="button">Ver detalle</button></div> : null}
          <div className="globe-explorer-caption"><span className="globe-live-dot" /> {selectedProperty ? 'Ubicación seleccionada' : 'Mapa interactivo'}</div>
        </div>
      </section>
    </main>
  </div>
}
