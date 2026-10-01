import { useEffect, useMemo, useState } from 'react'
import { InmuebleCard } from '../../components/inmuebles/InmuebleCard.tsx'
import { InmuebleFilters } from '../../components/inmuebles/InmuebleFilters.tsx'
import { InmuebleTable } from '../../components/inmuebles/InmuebleTable.tsx'
import { EmptyState } from '../../components/ui/EmptyState.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { listCategorias } from '../../services/categorias.ts'
import { listInmuebles } from '../../services/inmuebles.ts'
import { listPropietarios } from '../../services/propietarios.ts'
import { ApiError } from '../../services/http.ts'
import type { CategoriaOption } from '../../types/categorias.ts'
import type { EstadoDisponibilidadInmueble, InmuebleFilters as InmuebleFilterState, InmuebleListParams, InmuebleSort, TipoOperacion } from '../../types/inmuebles.ts'
import type { PropietarioOption } from '../../types/propietarios.ts'
import { ForbiddenPage } from '../errors/ForbiddenPage.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { navigate } from '../../router/navigation.ts'

const DEFAULT_FILTERS: InmuebleFilterState = {
  q: '', tipo_operacion: '', estado_disponibilidad: '', categoria_id: '', propietario_id: '', habitaciones: '', banos_completos: '', publicado: '', municipio: '', estado_ubicacion: '', page: 1, per_page: 15, sort: 'created_at', direction: 'desc',
}

const allowedSorts: InmuebleSort[] = ['id', 'codigo', 'titulo', 'tipo_operacion', 'estado_disponibilidad', 'created_at', 'updated_at']

function isTipoOperacion(value: string): value is TipoOperacion {
  return value === 'venta' || value === 'renta'
}

function isEstadoDisponibilidad(value: string): value is EstadoDisponibilidadInmueble {
  return value === 'disponible' || value === 'vendido' || value === 'rentado' || value === 'inactivo'
}

function validNumber(value: string | null, fallback = ''): string {
  return value !== null && /^\d+$/.test(value) ? value : fallback
}

function readFiltersFromUrl(): InmuebleFilterState {
  const params = new URLSearchParams(window.location.search)
  const tipo = params.get('tipo_operacion') ?? ''
  const estado = params.get('estado_disponibilidad') ?? ''
  const sort = params.get('sort') ?? ''
  const direction = params.get('direction') ?? ''
  const perPage = params.get('per_page')
  const parsedPerPage = perPage === '25' || perPage === '50' ? Number(perPage) as 25 | 50 : 15
  return {
    ...DEFAULT_FILTERS,
    q: params.get('q') ?? '',
    tipo_operacion: isTipoOperacion(tipo) ? tipo : '',
    estado_disponibilidad: isEstadoDisponibilidad(estado) ? estado : '',
    categoria_id: validNumber(params.get('categoria_id')),
    propietario_id: validNumber(params.get('propietario_id')),
    habitaciones: validNumber(params.get('habitaciones')),
    banos_completos: validNumber(params.get('banos_completos')),
    publicado: params.get('publicado') === 'true' || params.get('publicado') === 'false' ? params.get('publicado') as 'true' | 'false' : '',
    municipio: params.get('municipio') ?? '',
    estado_ubicacion: params.get('estado_ubicacion') ?? '',
    page: Math.max(1, Number(params.get('page') ?? 1) || 1),
    per_page: parsedPerPage,
    sort: allowedSorts.includes(sort as InmuebleSort) ? sort as InmuebleSort : 'created_at',
    direction: direction === 'asc' ? 'asc' : 'desc',
  }
}

function updateUrl(filters: InmuebleFilterState): void {
  const params = new URLSearchParams()
  const entries: Array<[string, string | number]> = [
    ['q', filters.q], ['tipo_operacion', filters.tipo_operacion], ['estado_disponibilidad', filters.estado_disponibilidad], ['categoria_id', filters.categoria_id], ['propietario_id', filters.propietario_id], ['habitaciones', filters.habitaciones], ['banos_completos', filters.banos_completos], ['publicado', filters.publicado], ['municipio', filters.municipio], ['estado_ubicacion', filters.estado_ubicacion],
  ]
  entries.forEach(([name, value]) => { if (value !== '') params.set(name, String(value)) })
  if (filters.page > 1) params.set('page', String(filters.page))
  if (filters.per_page !== 15) params.set('per_page', String(filters.per_page))
  if (filters.sort !== 'created_at') params.set('sort', filters.sort)
  if (filters.direction !== 'desc') params.set('direction', filters.direction)
  const query = params.toString()
  window.history.replaceState({}, '', `/inmuebles${query ? `?${query}` : ''}`)
}

function toRequestParams(filters: InmuebleFilterState): InmuebleListParams {
  return {
    q: filters.q || undefined,
    tipo_operacion: filters.tipo_operacion || undefined,
    estado_disponibilidad: filters.estado_disponibilidad || undefined,
    categoria_id: filters.categoria_id ? Number(filters.categoria_id) : undefined,
    propietario_id: filters.propietario_id ? Number(filters.propietario_id) : undefined,
    habitaciones: filters.habitaciones ? Number(filters.habitaciones) : undefined,
    banos_completos: filters.banos_completos ? Number(filters.banos_completos) : undefined,
    publicado: filters.publicado === '' ? undefined : filters.publicado === 'true',
    municipio: filters.municipio || undefined,
    estado_ubicacion: filters.estado_ubicacion || undefined,
    page: filters.page,
    per_page: filters.per_page,
    sort: filters.sort,
    direction: filters.direction,
  }
}

function hasActiveFilters(filters: InmuebleFilterState): boolean {
  return Boolean(filters.q || filters.tipo_operacion || filters.estado_disponibilidad || filters.categoria_id || filters.propietario_id || filters.habitaciones || filters.banos_completos || filters.publicado || filters.municipio || filters.estado_ubicacion)
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

export function InmueblesPage(): React.JSX.Element {
  const { can } = useAuth()
  const [filters, setFilters] = useState<InmuebleFilterState>(readFiltersFromUrl)
  const [response, setResponse] = useState<Awaited<ReturnType<typeof listInmuebles>> | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [categories, setCategories] = useState<CategoriaOption[] | null>(null)
  const [owners, setOwners] = useState<PropietarioOption[] | null>(null)
  const [catalogsLoading, setCatalogsLoading] = useState(true)

  useEffect(() => {
    const controller = new AbortController()
    updateUrl(filters)
    const timeout = window.setTimeout(() => {
      setIsLoading(true)
      setError(null)
      listInmuebles(toRequestParams(filters), controller.signal)
        .then(setResponse)
        .catch((nextError: unknown) => { if (!isAbortError(nextError)) { setResponse(null); setError(nextError) } })
        .finally(() => { if (!controller.signal.aborted) setIsLoading(false) })
    }, filters.q ? 350 : 0)
    return () => { window.clearTimeout(timeout); controller.abort() }
  }, [filters])

  useEffect(() => {
    const controller = new AbortController()
    Promise.allSettled([listCategorias(controller.signal), listPropietarios(controller.signal)])
      .then(([categoryResult, ownerResult]) => {
        if (categoryResult.status === 'fulfilled') setCategories(categoryResult.value.data)
        if (ownerResult.status === 'fulfilled') setOwners(ownerResult.value.data)
      })
      .finally(() => { if (!controller.signal.aborted) setCatalogsLoading(false) })
    return () => controller.abort()
  }, [])

  useEffect(() => {
    const handlePopState = (): void => setFilters(readFiltersFromUrl())
    window.addEventListener('popstate', handlePopState)
    return () => window.removeEventListener('popstate', handlePopState)
  }, [])

  const updateFilter = (field: keyof InmuebleFilterState, value: string): void => {
    setFilters((current) => ({ ...current, [field]: value, page: 1 }))
  }

  const clearFilters = (): void => setFilters(DEFAULT_FILTERS)
  const currentItems = response?.data ?? []
  const meta = response?.meta
  const isForbidden = error instanceof ApiError && error.status === 403
  const pageWindow = useMemo(() => {
    if (!meta) return []
    const first = Math.max(1, meta.current_page - 2)
    const last = Math.min(meta.last_page, first + 4)
    return Array.from({ length: last - first + 1 }, (_, index) => first + index)
  }, [meta])

  if (isForbidden) return <ForbiddenPage />

  return <div className="space-y-6">
    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><nav aria-label="Migas de pan" className="flex items-center gap-2 text-xs font-semibold text-[var(--app-text-muted)]"><a className="hover:text-[var(--app-accent)]" href="/dashboard">Inicio</a><span aria-hidden="true">›</span><span className="text-[var(--app-accent)]">Inmuebles</span></nav><h1 className="mt-2 text-2xl font-bold tracking-[-0.035em] text-[var(--app-text)] sm:text-3xl">Inmuebles</h1><p className="mt-2 text-sm text-[var(--app-text-muted)]">Administra y consulta las propiedades registradas.</p></div><div className="flex flex-wrap items-center gap-3">{meta ? <p className="text-sm font-semibold text-[var(--app-text-muted)]">{meta.total} {meta.total === 1 ? 'inmueble' : 'inmuebles'}</p> : null}{can('inmuebles.crear') ? <button className="app-button-primary" onClick={() => navigate('/inmuebles/nuevo')} type="button">+ Nuevo inmueble</button> : null}</div></div>
    <InmuebleFilters catalogsLoading={catalogsLoading} categories={categories} filters={filters} onChange={updateFilter} onClear={clearFilters} owners={owners} />
    {error && !isForbidden ? <ErrorState message={error instanceof ApiError && error.status >= 500 ? 'El servicio no respondió correctamente. Intenta nuevamente.' : undefined} onRetry={() => setFilters((current) => ({ ...current }))} /> : null}
    {isLoading && !response ? <LoadingState label="Cargando inmuebles..." /> : null}
    {!isLoading && !error && response && currentItems.length === 0 ? <EmptyState message={hasActiveFilters(filters) ? 'No encontramos inmuebles con estos filtros.' : 'Aún no hay inmuebles registrados.'} title={hasActiveFilters(filters) ? 'Sin coincidencias' : 'Sin inmuebles'} /> : null}
    {currentItems.length > 0 ? <><section aria-label="Listado de inmuebles" className="app-card overflow-hidden"><InmuebleTable properties={currentItems} /><div className="space-y-3 p-4 lg:hidden">{currentItems.map((property) => <InmuebleCard key={property.id} property={property} />)}</div><div className="flex flex-col gap-4 border-t border-[var(--app-border)] px-4 py-4 text-sm sm:flex-row sm:items-center sm:justify-between"><p className="text-[var(--app-text-muted)]">Mostrando <span className="font-semibold text-[var(--app-text)]">{meta?.from ?? 0}–{meta?.to ?? 0}</span> de <span className="font-semibold text-[var(--app-text)]">{meta?.total ?? currentItems.length}</span> inmuebles</p><div className="flex items-center gap-3"><span className="text-[var(--app-text-muted)]">Por página</span><select aria-label="Inmuebles por página" className="app-select" onChange={(event) => updateFilter('per_page', event.target.value)} value={filters.per_page}><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></div></div></section>{meta && meta.last_page > 1 ? <nav aria-label="Paginación de inmuebles" className="flex flex-wrap items-center justify-center gap-1"><button aria-label="Página anterior" className="app-button-secondary px-3" disabled={!response.links.prev} onClick={() => setFilters((current) => ({ ...current, page: Math.max(1, current.page - 1) }))} type="button">Anterior</button>{pageWindow.map((page) => <button aria-current={page === meta.current_page ? 'page' : undefined} className={page === meta.current_page ? 'app-button-primary px-3' : 'app-button-secondary px-3'} key={page} onClick={() => setFilters((current) => ({ ...current, page }))} type="button">{page}</button>)}<button aria-label="Página siguiente" className="app-button-secondary px-3" disabled={!response.links.next} onClick={() => setFilters((current) => ({ ...current, page: Math.min(meta.last_page, current.page + 1) }))} type="button">Siguiente</button></nav> : null}</> : null}
  </div>
}
