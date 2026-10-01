import { useEffect, useMemo, useState } from 'react'
import { BrandLogo } from '../../components/auth/BrandLogo.tsx'
import { PublicPropertyCard } from '../../components/public/PublicPropertyCard.tsx'
import { EmptyState } from '../../components/ui/EmptyState.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { ApiError } from '../../services/http.ts'
import { listPublicInmuebles } from '../../services/public-inmuebles.ts'
import type { PublicInmuebleFilters } from '../../types/public-inmuebles.ts'

const DEFAULT_FILTERS: PublicInmuebleFilters = {
  q: '', tipo_operacion: '', page: 1, per_page: 12, sort: 'created_at', direction: 'desc',
}

function readFiltersFromUrl(): PublicInmuebleFilters {
  const params = new URLSearchParams(window.location.search)
  const tipo = params.get('tipo_operacion')
  const perPage = params.get('per_page')
  const sort = params.get('sort')
  const direction = params.get('direction')

  return {
    ...DEFAULT_FILTERS,
    q: params.get('q') ?? '',
    tipo_operacion: tipo === 'venta' || tipo === 'renta' ? tipo : '',
    page: Math.max(1, Number(params.get('page') ?? 1) || 1),
    per_page: perPage === '24' ? 24 : 12,
    sort: sort === 'titulo' ? 'titulo' : 'created_at',
    direction: direction === 'asc' ? 'asc' : 'desc',
  }
}

function updateUrl(filters: PublicInmuebleFilters): void {
  const params = new URLSearchParams()
  if (filters.q) params.set('q', filters.q)
  if (filters.tipo_operacion) params.set('tipo_operacion', filters.tipo_operacion)
  if (filters.page > 1) params.set('page', String(filters.page))
  if (filters.per_page !== 12) params.set('per_page', String(filters.per_page))
  if (filters.sort !== 'created_at') params.set('sort', filters.sort)
  if (filters.direction !== 'desc') params.set('direction', filters.direction)
  const query = params.toString()
  window.history.replaceState({}, '', `/propiedades${query ? `?${query}` : ''}`)
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

export function PropiedadesPage(): React.JSX.Element {
  const [filters, setFilters] = useState<PublicInmuebleFilters>(readFiltersFromUrl)
  const [response, setResponse] = useState<Awaited<ReturnType<typeof listPublicInmuebles>> | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const controller = new AbortController()
    const timeout = window.setTimeout(() => {
      updateUrl(filters)
      setIsLoading(true)
      setError(null)
      listPublicInmuebles(filters, controller.signal)
        .then(setResponse)
        .catch((nextError: unknown) => { if (!isAbortError(nextError)) { setResponse(null); setError(nextError) } })
        .finally(() => { if (!controller.signal.aborted) setIsLoading(false) })
    }, filters.q ? 350 : 0)

    return () => { window.clearTimeout(timeout); controller.abort() }
  }, [filters])

  useEffect(() => {
    const handlePopState = (): void => setFilters(readFiltersFromUrl())
    window.addEventListener('popstate', handlePopState)
    return () => window.removeEventListener('popstate', handlePopState)
  }, [])

  const updateFilter = (field: keyof PublicInmuebleFilters, value: string): void => {
    setFilters((current) => ({ ...current, [field]: value, page: 1 }))
  }
  const clearFilters = (): void => setFilters(DEFAULT_FILTERS)
  const items = response?.data ?? []
  const meta = response?.meta
  const pageWindow = useMemo(() => {
    if (!meta) return []
    const first = Math.max(1, meta.current_page - 2)
    const last = Math.min(meta.last_page, first + 4)
    return Array.from({ length: last - first + 1 }, (_, index) => first + index)
  }, [meta])

  return <main className="app-public-page min-h-screen">
    <header className="border-b border-white/10 bg-[#0b294d]"><div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8"><a aria-label="Ir al inicio público" href="/propiedades"><BrandLogo compact variant="light" /></a><a className="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/25 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-white/25" href="/login">Acceso administrativo</a></div></header>
    <div className="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
      <section className="max-w-3xl"><p className="text-sm font-bold uppercase tracking-[0.14em] text-[var(--app-accent)]">SotyTech · Gestión inmobiliaria</p><h1 className="mt-3 text-4xl font-black tracking-[-0.055em] text-[var(--app-text)] sm:text-5xl">Encuentra un espacio para tu próxima historia</h1><p className="mt-4 max-w-2xl text-base leading-7 text-[var(--app-text-muted)]">Explora propiedades publicadas y encuentra opciones que se adapten a lo que estás buscando.</p></section>
      <section aria-label="Filtros de propiedades" className="app-card mt-8 p-4 sm:p-5"><div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_12rem_12rem_auto]"><label className="block text-sm font-semibold text-[var(--app-text)]">Buscar<input aria-label="Buscar propiedades" className="app-input mt-2" onChange={(event) => updateFilter('q', event.target.value)} placeholder="Código, título, descripción o ubicación" type="search" value={filters.q} /></label><label className="block text-sm font-semibold text-[var(--app-text)]">Operación<select className="app-select mt-2 w-full" onChange={(event) => updateFilter('tipo_operacion', event.target.value)} value={filters.tipo_operacion}><option value="">Todas</option><option value="venta">Venta</option><option value="renta">Renta</option></select></label><label className="block text-sm font-semibold text-[var(--app-text)]">Ordenar<select className="app-select mt-2 w-full" onChange={(event) => { const value = event.target.value as PublicInmuebleFilters['sort']; setFilters((current) => ({ ...current, sort: value, page: 1 })) }} value={filters.sort}><option value="created_at">Más recientes</option><option value="titulo">Nombre</option></select></label><button className="app-button-secondary self-end" onClick={clearFilters} type="button">Limpiar</button></div></section>
      {error ? <div className="mt-6"><ErrorState message={error instanceof ApiError && error.status >= 500 ? 'El catálogo público no respondió correctamente.' : undefined} onRetry={() => setFilters((current) => ({ ...current }))} /></div> : null}
      {isLoading && !response ? <div className="mt-8"><LoadingState label="Cargando propiedades..." /></div> : null}
      {!isLoading && !error && response && items.length === 0 ? <div className="mt-8"><EmptyState message={filters.q || filters.tipo_operacion ? 'Prueba con otros criterios de búsqueda.' : 'Próximamente publicaremos nuevas propiedades.'} title="No encontramos propiedades" /></div> : null}
      {items.length > 0 ? <><div className="mt-8 flex items-center justify-between gap-4"><p className="text-sm text-[var(--app-text-muted)]">{meta?.total ?? items.length} propiedades publicadas</p><label className="flex items-center gap-2 text-sm text-[var(--app-text-muted)]">Ver<select aria-label="Propiedades por página" className="app-select" onChange={(event) => updateFilter('per_page', event.target.value)} value={filters.per_page}><option value="12">12</option><option value="24">24</option></select></label></div><section aria-label="Propiedades publicadas" className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{items.map((property) => <PublicPropertyCard key={property.id} property={property} />)}</section>{meta && meta.last_page > 1 ? <nav aria-label="Paginación de propiedades" className="mt-8 flex flex-wrap items-center justify-center gap-2"><button aria-label="Página anterior" className="app-button-secondary" disabled={!response.links.prev} onClick={() => setFilters((current) => ({ ...current, page: Math.max(1, current.page - 1) }))} type="button">Anterior</button>{pageWindow.map((page) => <button aria-current={page === meta.current_page ? 'page' : undefined} className={page === meta.current_page ? 'app-button-primary' : 'app-button-secondary'} key={page} onClick={() => setFilters((current) => ({ ...current, page }))} type="button">{page}</button>)}<button aria-label="Página siguiente" className="app-button-secondary" disabled={!response.links.next} onClick={() => setFilters((current) => ({ ...current, page: Math.min(meta.last_page, current.page + 1) }))} type="button">Siguiente</button></nav> : null}</> : null}
    </div>
  </main>
}
