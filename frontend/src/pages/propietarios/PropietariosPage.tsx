import { useEffect, useMemo, useState } from 'react'
import { PropietarioCard } from '../../components/propietarios/PropietarioCard.tsx'
import { PropietarioFilters } from '../../components/propietarios/PropietarioFilters.tsx'
import { PropietarioTable } from '../../components/propietarios/PropietarioTable.tsx'
import { EmptyState } from '../../components/ui/EmptyState.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { ApiError } from '../../services/http.ts'
import { listPropietariosPage } from '../../services/propietarios.ts'
import type { EstadoRegistroPropietario, PropietarioFilters as PropietarioFilterState, PropietarioListParams, PropietarioSort, TipoPersonaPropietario } from '../../types/propietarios.ts'
import { ForbiddenPage } from '../errors/ForbiddenPage.tsx'

const DEFAULT_FILTERS: PropietarioFilterState = { q: '', tipo_persona: '', estado_registro: '', page: 1, per_page: 15, sort: 'created_at', direction: 'desc' }
const allowedSorts: PropietarioSort[] = ['id', 'nombre_razon_social', 'rfc', 'created_at', 'updated_at']

function isTipoPersona(value: string): value is TipoPersonaPropietario {
  return value === 'fisica' || value === 'moral'
}

function isEstadoRegistro(value: string): value is EstadoRegistroPropietario {
  return value === 'activo' || value === 'inactivo'
}

function readFiltersFromUrl(): PropietarioFilterState {
  const params = new URLSearchParams(window.location.search)
  const tipo = params.get('tipo_persona') ?? ''
  const estado = params.get('estado_registro') ?? ''
  const sort = params.get('sort') ?? ''
  return { ...DEFAULT_FILTERS, q: params.get('q') ?? '', tipo_persona: isTipoPersona(tipo) ? tipo : '', estado_registro: isEstadoRegistro(estado) ? estado : '', page: Math.max(1, Number(params.get('page') ?? 1) || 1), per_page: params.get('per_page') === '25' || params.get('per_page') === '50' ? Number(params.get('per_page')) as 25 | 50 : 15, sort: allowedSorts.includes(sort as PropietarioSort) ? sort as PropietarioSort : 'created_at', direction: params.get('direction') === 'asc' ? 'asc' : 'desc' }
}

function updateUrl(filters: PropietarioFilterState): void {
  const params = new URLSearchParams()
  if (filters.q) params.set('q', filters.q)
  if (filters.tipo_persona) params.set('tipo_persona', filters.tipo_persona)
  if (filters.estado_registro) params.set('estado_registro', filters.estado_registro)
  if (filters.page > 1) params.set('page', String(filters.page))
  if (filters.per_page !== 15) params.set('per_page', String(filters.per_page))
  if (filters.sort !== 'created_at') params.set('sort', filters.sort)
  if (filters.direction !== 'desc') params.set('direction', filters.direction)
  const query = params.toString()
  window.history.replaceState({}, '', `/propietarios${query ? `?${query}` : ''}`)
}

function toRequestParams(filters: PropietarioFilterState): PropietarioListParams {
  return { q: filters.q || undefined, tipo_persona: filters.tipo_persona || undefined, estado_registro: filters.estado_registro || undefined, page: filters.page, per_page: filters.per_page, sort: filters.sort, direction: filters.direction }
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

function hasActiveFilters(filters: PropietarioFilterState): boolean {
  return Boolean(filters.q || filters.tipo_persona || filters.estado_registro)
}

export function PropietariosPage(): React.JSX.Element {
  const [filters, setFilters] = useState<PropietarioFilterState>(readFiltersFromUrl)
  const [response, setResponse] = useState<Awaited<ReturnType<typeof listPropietariosPage>> | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const controller = new AbortController()
    updateUrl(filters)
    const timeout = window.setTimeout(() => {
      setIsLoading(true)
      setError(null)
      listPropietariosPage(toRequestParams(filters), controller.signal)
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

  const updateFilter = (field: keyof PropietarioFilterState, value: string): void => setFilters((current) => ({ ...current, [field]: value, page: 1 }))
  const currentItems = response?.data ?? []
  const meta = response?.meta
  const isForbidden = error instanceof ApiError && error.status === 403
  const pageWindow = useMemo(() => { if (!meta) return []; const first = Math.max(1, meta.current_page - 2); const last = Math.min(meta.last_page, first + 4); return Array.from({ length: last - first + 1 }, (_, index) => first + index) }, [meta])

  if (isForbidden) return <ForbiddenPage />

  return <div className="space-y-6">
    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><nav aria-label="Migas de pan" className="flex items-center gap-2 text-xs font-semibold text-[var(--app-text-muted)]"><a className="hover:text-[var(--app-accent)]" href="/dashboard">Inicio</a><span aria-hidden="true">›</span><span className="text-[var(--app-accent)]">Propietarios</span></nav><h1 className="mt-2 text-2xl font-bold tracking-[-0.035em] text-[var(--app-text)] sm:text-3xl">Propietarios</h1><p className="mt-2 text-sm text-[var(--app-text-muted)]">Administra y consulta a los propietarios registrados.</p></div>{meta ? <p className="text-sm font-semibold text-[var(--app-text-muted)]">{meta.total} {meta.total === 1 ? 'propietario' : 'propietarios'}</p> : null}</div>
    <PropietarioFilters filters={filters} onChange={updateFilter} onClear={() => setFilters(DEFAULT_FILTERS)} />
    {error && !isForbidden ? <ErrorState message={error instanceof ApiError && error.status >= 500 ? 'El servicio no respondió correctamente. Intenta nuevamente.' : undefined} onRetry={() => setFilters((current) => ({ ...current }))} /> : null}
    {isLoading && !response ? <LoadingState label="Cargando propietarios..." /> : null}
    {!isLoading && !error && response && currentItems.length === 0 ? <EmptyState message={hasActiveFilters(filters) ? 'No encontramos propietarios con estos filtros.' : 'Aún no hay propietarios registrados.'} title={hasActiveFilters(filters) ? 'Sin coincidencias' : 'Sin propietarios'} /> : null}
    {currentItems.length > 0 ? <><section aria-label="Listado de propietarios" className="app-card overflow-hidden"><PropietarioTable owners={currentItems} /><div className="space-y-3 p-4 lg:hidden">{currentItems.map((owner) => <PropietarioCard key={owner.id} owner={owner} />)}</div><div className="flex flex-col gap-4 border-t border-[var(--app-border)] px-4 py-4 text-sm sm:flex-row sm:items-center sm:justify-between"><p className="text-[var(--app-text-muted)]">Mostrando <span className="font-semibold text-[var(--app-text)]">{meta?.from ?? 0}–{meta?.to ?? 0}</span> de <span className="font-semibold text-[var(--app-text)]">{meta?.total ?? currentItems.length}</span> propietarios</p><div className="flex items-center gap-3"><span className="text-[var(--app-text-muted)]">Por página</span><select aria-label="Propietarios por página" className="app-select" onChange={(event) => updateFilter('per_page', event.target.value)} value={filters.per_page}><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></div></div></section>{meta && meta.last_page > 1 ? <nav aria-label="Paginación de propietarios" className="flex flex-wrap items-center justify-center gap-1"><button aria-label="Página anterior" className="app-button-secondary px-3" disabled={!response.links.prev} onClick={() => setFilters((current) => ({ ...current, page: Math.max(1, current.page - 1) }))} type="button">Anterior</button>{pageWindow.map((page) => <button aria-current={page === meta.current_page ? 'page' : undefined} className={page === meta.current_page ? 'app-button-primary px-3' : 'app-button-secondary px-3'} key={page} onClick={() => setFilters((current) => ({ ...current, page }))} type="button">{page}</button>)}<button aria-label="Página siguiente" className="app-button-secondary px-3" disabled={!response.links.next} onClick={() => setFilters((current) => ({ ...current, page: Math.min(meta.last_page, current.page + 1) }))} type="button">Siguiente</button></nav> : null}</> : null}
  </div>
}
