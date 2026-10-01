import { useEffect, useMemo, useState } from 'react'
import { PublicNavbar } from '../components/PublicNavbar.tsx'
import { PublicPropertyCard } from '../components/PublicPropertyCard.tsx'
import { EmptyState, ErrorState, LoadingState } from '../components/PublicStates.tsx'
import { PublicApiError } from '../http.ts'
import { navigate } from '../navigation.ts'
import { listProperties } from '../services.ts'
import type { PublicFilters } from '../types.ts'

const DEFAULT_FILTERS: PublicFilters = {
  q: '',
  ubicacion: '',
  tipo_inmueble: '',
  tipo_operacion: '',
  precio_min: '',
  precio_max: '',
  page: 1,
  per_page: 12,
  sort: 'created_at',
  direction: 'desc',
}

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
    page: Math.max(1, Number(params.get('page') ?? 1) || 1),
    per_page: params.get('per_page') === '24' ? 24 : 12,
    sort: params.get('sort') === 'titulo' ? 'titulo' : 'created_at',
    direction: params.get('direction') === 'asc' ? 'asc' : 'desc',
  }
}

function updateUrl(filters: PublicFilters): void {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(filters)) {
    if (key === 'page' && value === 1) continue
    if (key === 'per_page' && value === 12) continue
    if (key === 'sort' && value === 'created_at') continue
    if (key === 'direction' && value === 'desc') continue
    if (value !== '') params.set(key, String(value))
  }
  const query = params.toString()
  window.history.replaceState({}, '', `/propiedades${query ? `?${query}` : ''}`)
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

type FilterField = 'q' | 'ubicacion' | 'tipo_inmueble' | 'tipo_operacion' | 'precio_min' | 'precio_max'

export function PropertiesPage({ theme, onToggleTheme }: { theme: 'light' | 'dark'; onToggleTheme: () => void }): React.JSX.Element {
  const [filters, setFilters] = useState<PublicFilters>(readFilters)
  const [response, setResponse] = useState<Awaited<ReturnType<typeof listProperties>> | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const controller = new AbortController()
    const timeout = window.setTimeout(() => {
      updateUrl(filters)
      setLoading(true)
      setError(null)
      listProperties(filters, controller.signal)
        .then(setResponse)
        .catch((nextError: unknown) => {
          if (!isAbortError(nextError)) {
            setResponse(null)
            setError(nextError)
          }
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

  const clear = (): void => {
    setFilters(DEFAULT_FILTERS)
    navigate('/propiedades')
  }

  const properties = response?.data ?? []
  const meta = response?.meta
  const pages = useMemo(() => {
    if (!meta) return []
    const first = Math.max(1, Math.min(meta.current_page - 2, meta.last_page - 4))
    const last = Math.min(meta.last_page, first + 4)
    return Array.from({ length: last - first + 1 }, (_, index) => first + index)
  }, [meta])
  const hasFilters = Boolean(filters.q || filters.ubicacion || filters.tipo_inmueble || filters.tipo_operacion || filters.precio_min || filters.precio_max)

  return <div className="public-page">
    <PublicNavbar onToggleTheme={onToggleTheme} theme={theme} />
    <main>
      <section className="public-hero">
        <div className="public-container grid items-end gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
          <div>
            <p className="public-kicker text-[var(--public-accent)]">SOTYTECH INMOBILIARIA</p>
            <h1 className="public-display mt-3 max-w-4xl text-4xl font-bold text-[var(--public-text)] sm:text-6xl">Encuentra un espacio que se sienta tuyo.</h1>
            <p className="mt-4 max-w-2xl text-lg leading-8 text-[var(--public-muted)]">Explora propiedades publicadas con información clara para comprar, rentar o dar el siguiente paso con calma.</p>
            <div className="mt-6 flex flex-wrap gap-3"><a className="public-button-primary" href="#propiedades">Explorar propiedades</a><a className="public-button-secondary" href="/contacto">Hablar con un asesor</a></div>
          </div>
          <div className="public-hero-aside">
            <p className="public-kicker text-[var(--public-accent)]">Busca a tu ritmo</p>
            <h2 className="public-display mt-3 text-2xl font-bold text-[var(--public-text)]">Una selección para tomar decisiones informadas.</h2>
            <p className="mt-3 text-sm leading-6 text-[var(--public-muted)]">Filtra por zona, operación, tipo y presupuesto. Abre cualquier ficha para consultar sus detalles y solicitar información.</p>
            <div className="mt-5 grid grid-cols-3 gap-3 border-t border-[var(--public-border)] pt-4 text-center"><div><strong className="block text-lg text-[var(--public-text)]">Compra</strong><span className="text-xs text-[var(--public-muted)]">Patrimonio</span></div><div><strong className="block text-lg text-[var(--public-text)]">Renta</strong><span className="text-xs text-[var(--public-muted)]">Flexibilidad</span></div><div><strong className="block text-lg text-[var(--public-text)]">Invierte</strong><span className="text-xs text-[var(--public-muted)]">Proyección</span></div></div>
          </div>
        </div>
      </section>

      <section className="public-container public-service-strip" aria-label="Beneficios de atención"><div><strong>Datos públicos claros</strong><span>Consulta ubicación, precio y características antes de preguntar.</span></div><div><strong>Visitas con cita</strong><span>Conoce cada propiedad con acompañamiento.</span></div><div><strong>Seguimiento cercano</strong><span>Un asesor te orienta en el siguiente paso.</span></div></section>

      <section className="public-container public-filter-anchor" id="propiedades">
        <form className="public-filter-panel" onSubmit={(event) => event.preventDefault()} aria-label="Filtrar propiedades">
          <div className="flex flex-wrap items-end justify-between gap-3"><div><p className="public-kicker text-[var(--public-accent)]">Catálogo público</p><h2 className="public-display mt-1 text-2xl font-bold text-[var(--public-text)]">Encuentra tu próxima propiedad</h2></div><button className="public-button-secondary" onClick={clear} type="button">Limpiar filtros</button></div>
          <div className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <label className="public-label lg:col-span-2">Buscar por palabra<input aria-label="Buscar por palabra" className="public-input mt-2" onChange={(event) => updateFilter('q', event.target.value)} placeholder="Código, título o descripción" type="search" value={filters.q} /></label>
            <label className="public-label">Ubicación<input aria-label="Filtrar por ubicación" className="public-input mt-2" onChange={(event) => updateFilter('ubicacion', event.target.value)} placeholder="Ciudad, estado o colonia" value={filters.ubicacion} /></label>
            <label className="public-label">Tipo de inmueble<input aria-label="Filtrar por tipo de inmueble" className="public-input mt-2" onChange={(event) => updateFilter('tipo_inmueble', event.target.value)} placeholder="Casa, departamento..." value={filters.tipo_inmueble} /></label>
            <label className="public-label">Operación<select className="public-input mt-2" onChange={(event) => updateFilter('tipo_operacion', event.target.value)} value={filters.tipo_operacion}><option value="">Venta y renta</option><option value="venta">Venta</option><option value="renta">Renta</option></select></label>
            <label className="public-label">Precio mínimo<input className="public-input mt-2" inputMode="numeric" min="0" onChange={(event) => updateFilter('precio_min', event.target.value)} placeholder="$ 0" type="number" value={filters.precio_min} /></label>
            <label className="public-label">Precio máximo<input className="public-input mt-2" inputMode="numeric" min="0" onChange={(event) => updateFilter('precio_max', event.target.value)} placeholder="Sin límite" type="number" value={filters.precio_max} /></label>
            <label className="public-label">Ordenar<select className="public-input mt-2" onChange={(event) => setFilters((current) => ({ ...current, sort: event.target.value as PublicFilters['sort'], page: 1 }))} value={filters.sort}><option value="created_at">Más recientes</option><option value="titulo">Nombre</option></select></label>
          </div>
        </form>
      </section>

      <section className="public-container py-10" aria-labelledby="available-properties-heading"><div className="flex flex-wrap items-end justify-between gap-4"><div><p className="public-kicker text-[var(--public-accent)]">Selección disponible</p><h2 className="public-display text-3xl font-bold text-[var(--public-text)]" id="available-properties-heading">Propiedades para explorar</h2><p className="mt-1 text-sm text-[var(--public-muted)]">{meta?.total ?? 0} opciones publicadas</p></div><label className="flex items-center gap-2 text-sm text-[var(--public-muted)]">Mostrar<select aria-label="Propiedades por página" className="public-input w-auto" onChange={(event) => setFilters((current) => ({ ...current, per_page: event.target.value === '24' ? 24 : 12, page: 1 }))} value={filters.per_page}><option value="12">12</option><option value="24">24</option></select></label></div>
        {error ? <div className="mt-7"><ErrorState detail={error instanceof PublicApiError && error.status >= 500 ? 'El catálogo público no respondió correctamente.' : undefined} onRetry={() => setFilters((current) => ({ ...current }))} /></div> : null}
        {loading && !response ? <div className="mt-7"><LoadingState /></div> : null}
        {!loading && !error && response && properties.length === 0 ? <div className="mt-7"><EmptyState filtered={hasFilters} /></div> : null}
        {properties.length > 0 ? <><div className="mt-7 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">{properties.map((property) => <PublicPropertyCard key={property.id} property={property} />)}</div>{meta && meta.last_page > 1 ? <nav aria-label="Paginación de propiedades" className="mt-10 flex flex-wrap items-center justify-center gap-2"><button className="public-button-secondary" disabled={!response?.links.prev} onClick={() => setFilters((current) => ({ ...current, page: Math.max(1, current.page - 1) }))} type="button">Anterior</button>{pages.map((page) => <button aria-current={page === meta.current_page ? 'page' : undefined} className={page === meta.current_page ? 'public-button-primary' : 'public-button-secondary'} key={page} onClick={() => setFilters((current) => ({ ...current, page }))} type="button">{page}</button>)}<button className="public-button-secondary" disabled={!response?.links.next} onClick={() => setFilters((current) => ({ ...current, page: Math.min(meta.last_page, current.page + 1) }))} type="button">Siguiente</button></nav> : null}</> : null}
      </section>
    </main>
    <PublicFooter />
  </div>
}

function PublicFooter(): React.JSX.Element {
  return <footer className="public-footer" id="contacto"><div className="public-container grid gap-8 py-10 text-sm md:grid-cols-[1.2fr_1fr_1fr]"><div><p className="text-lg font-black text-[var(--public-text)]">SotyTech</p><p className="mt-2 max-w-sm leading-6">Soluciones inmobiliarias para comprar, rentar o invertir con información clara.</p></div><div><p className="public-kicker text-[var(--public-accent)]">Atención</p><p className="mt-3">Lunes a viernes · 9:00 a 18:00</p><a className="mt-1 block text-[var(--public-accent)]" href="/contacto">Solicitar asesoría</a></div><div><p className="public-kicker text-[var(--public-accent)]">Información</p><a className="mt-3 block hover:text-[var(--public-text)]" href="/aviso-de-privacidad">Aviso de privacidad</a><a className="mt-1 block hover:text-[var(--public-text)]" href="#propiedades">Propiedades disponibles</a><a className="mt-1 block hover:text-[var(--public-text)]" href="/contacto">Contacto y ubicación</a></div></div><div className="border-t border-[var(--public-border)]"><div className="public-container py-4 text-xs">© 2026 SotyTech · Bienes raíces</div></div></footer>
}
