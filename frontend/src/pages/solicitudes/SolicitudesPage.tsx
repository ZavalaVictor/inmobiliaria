import { useEffect, useMemo, useState } from 'react'
import { EmptyState } from '../../components/ui/EmptyState.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { SolicitudStatusBadge } from '../../components/solicitudes/SolicitudStatusBadge.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { navigate } from '../../router/navigation.ts'
import { createSolicitud, listSolicitudes } from '../../services/solicitudes.ts'
import { listInmuebles } from '../../services/inmuebles.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'
import type { InmuebleListItem } from '../../types/inmuebles.ts'
import type { EstadoSolicitud, MedioSolicitud, SolicitudCreatePayload, SolicitudInformacion, SolicitudSort } from '../../types/solicitudes.ts'
import { getSolicitudMediumLabel } from '../../utils/solicitudes.ts'

interface SolicitudFilters {
  q: string
  estado: EstadoSolicitud | ''
  medio_preferido: MedioSolicitud | ''
  origen: string
  page: number
  per_page: 15 | 25 | 50
  sort: SolicitudSort
  direction: 'asc' | 'desc'
}

const DEFAULT_FILTERS: SolicitudFilters = {
  q: '', estado: '', medio_preferido: '', origen: '', page: 1, per_page: 15, sort: 'fecha_solicitud', direction: 'desc',
}

const statuses: Array<{ value: EstadoSolicitud; label: string }> = [
  { value: 'nueva', label: 'Nueva' },
  { value: 'en_atencion', label: 'En atención' },
  { value: 'atendida', label: 'Atendida' },
  { value: 'descartada', label: 'Descartada' },
]

const mediums: Array<{ value: MedioSolicitud; label: string }> = [
  { value: 'telefono', label: 'Teléfono' },
  { value: 'whatsapp', label: 'WhatsApp' },
  { value: 'correo', label: 'Correo' },
]

function isStatus(value: string): value is EstadoSolicitud {
  return statuses.some((status) => status.value === value)
}

function isMedium(value: string): value is MedioSolicitud {
  return mediums.some((medium) => medium.value === value)
}

function readFiltersFromUrl(): SolicitudFilters {
  const params = new URLSearchParams(window.location.search)
  const perPage = Number(params.get('per_page') ?? '15')
  return {
    ...DEFAULT_FILTERS,
    q: params.get('q') ?? '',
    estado: isStatus(params.get('estado') ?? '') ? params.get('estado') as EstadoSolicitud : '',
    medio_preferido: isMedium(params.get('medio_preferido') ?? '') ? params.get('medio_preferido') as MedioSolicitud : '',
    origen: params.get('origen') ?? '',
    page: Math.max(1, Number(params.get('page') ?? 1) || 1),
    per_page: perPage === 25 || perPage === 50 ? perPage : 15,
    sort: 'fecha_solicitud',
    direction: params.get('direction') === 'asc' ? 'asc' : 'desc',
  }
}

function updateUrl(filters: SolicitudFilters): void {
  const params = new URLSearchParams()
  if (filters.q) params.set('q', filters.q)
  if (filters.estado) params.set('estado', filters.estado)
  if (filters.medio_preferido) params.set('medio_preferido', filters.medio_preferido)
  if (filters.origen) params.set('origen', filters.origen)
  if (filters.page > 1) params.set('page', String(filters.page))
  if (filters.per_page !== 15) params.set('per_page', String(filters.per_page))
  if (filters.direction !== 'desc') params.set('direction', filters.direction)
  const query = params.toString()
  window.history.replaceState({}, '', `/solicitudes${query ? `?${query}` : ''}`)
}

function formatDate(value: string | null): string {
  if (!value) return '—'
  return new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}

function personName(request: SolicitudInformacion): string {
  return request.nombre || request.email
}

function propertyLabel(request: SolicitudInformacion): string {
  return request.inmueble ? `${request.inmueble.codigo} · ${request.inmueble.titulo}` : 'Sin inmueble asociado'
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

function FieldError({ errors, field }: { errors: Record<string, string[]>; field: string }): React.JSX.Element | null {
  const message = errors[field]?.[0]
  return message ? <p className="mt-1 text-xs font-semibold text-[var(--app-danger)]">{message}</p> : null
}

interface CreateModalProps {
  onClose: () => void
  onCreated: (request: SolicitudInformacion) => void
}

function CreateSolicitudModal({ onClose, onCreated }: CreateModalProps): React.JSX.Element {
  const [properties, setProperties] = useState<InmuebleListItem[]>([])
  const [values, setValues] = useState<SolicitudCreatePayload>({ nombre: '', email: '', telefono: '', mensaje: '', medio_preferido: 'correo', inmueble_id: null })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [loadError, setLoadError] = useState(false)

  useEffect(() => {
    const controller = new AbortController()
    listInmuebles({ page: 1, per_page: 100, sort: 'created_at', direction: 'desc' }, controller.signal)
      .then((response) => setProperties(response.data))
      .catch((error: unknown) => { if (!isAbortError(error)) setLoadError(true) })
    return () => controller.abort()
  }, [])

  function updateValue(field: keyof SolicitudCreatePayload, value: string | number | null): void {
    setValues((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: [] }))
  }

  async function submit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault()
    setIsSubmitting(true)
    setErrors({})
    try {
      const created = await createSolicitud(values)
      onCreated(created)
    } catch (error: unknown) {
      setErrors(getValidationErrors(error instanceof ApiError ? error.payload : null))
    } finally {
      setIsSubmitting(false)
    }
  }

  return <div aria-modal="true" className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-4" role="dialog" onMouseDown={(event) => { if (event.currentTarget === event.target) onClose() }}>
    <form className="max-h-[min(760px,calc(100vh-2rem))] w-full max-w-2xl overflow-y-auto rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-5 shadow-2xl sm:p-7" onSubmit={(event) => void submit(event)}>
      <div className="flex items-start justify-between gap-4"><div><p className="text-xs font-bold uppercase tracking-[0.14em] text-[var(--app-accent)]">CRM</p><h2 className="mt-2 text-2xl font-bold text-[var(--app-text)]">Nueva solicitud</h2><p className="mt-2 text-sm text-[var(--app-text-muted)]">Registra una solicitud recibida fuera del formulario público.</p></div><button aria-label="Cerrar" className="app-icon-button" onClick={onClose} type="button">×</button></div>
      <div className="mt-6 grid gap-4 sm:grid-cols-2">
        <label className="sm:col-span-2"><span className="app-label">Nombre completo</span><input className="app-input" onChange={(event) => updateValue('nombre', event.target.value)} required value={values.nombre} /> <FieldError errors={errors} field="nombre" /></label>
        <label><span className="app-label">Correo electrónico</span><input className="app-input" onChange={(event) => updateValue('email', event.target.value)} required type="email" value={values.email} /> <FieldError errors={errors} field="email" /></label>
        <label><span className="app-label">Teléfono</span><input className="app-input" onChange={(event) => updateValue('telefono', event.target.value)} value={values.telefono ?? ''} /> <FieldError errors={errors} field="telefono" /></label>
        <label><span className="app-label">Medio preferido</span><select className="app-select w-full" onChange={(event) => updateValue('medio_preferido', event.target.value as MedioSolicitud)} value={values.medio_preferido ?? ''}><option value="">No especificado</option>{mediums.map((medium) => <option key={medium.value} value={medium.value}>{medium.label}</option>)}</select> <FieldError errors={errors} field="medio_preferido" /></label>
        <label><span className="app-label">Inmueble consultado</span><select className="app-select w-full" disabled={loadError} onChange={(event) => updateValue('inmueble_id', event.target.value ? Number(event.target.value) : null)} value={values.inmueble_id ?? ''}><option value="">Sin inmueble</option>{properties.map((property) => <option key={property.id} value={property.id}>{property.codigo} · {property.titulo}</option>)}</select>{loadError ? <p className="mt-1 text-xs text-[var(--app-text-muted)]">No pudimos cargar inmuebles; puedes guardar sin asociarlo.</p> : null}<FieldError errors={errors} field="inmueble_id" /></label>
        <label className="sm:col-span-2"><span className="app-label">Mensaje</span><textarea className="app-input min-h-28 resize-y" onChange={(event) => updateValue('mensaje', event.target.value)} value={values.mensaje ?? ''} /> <FieldError errors={errors} field="mensaje" /></label>
      </div>
      {errors.form ? <p className="mt-4 rounded-xl bg-[var(--app-danger-surface)] p-3 text-sm font-semibold text-[var(--app-danger)]">{errors.form[0]}</p> : null}
      <div className="mt-7 flex flex-col-reverse justify-end gap-3 sm:flex-row"><button className="app-button-secondary" onClick={onClose} type="button">Cancelar</button><button className="app-button-primary" disabled={isSubmitting} type="submit">{isSubmitting ? 'Guardando…' : 'Guardar solicitud'}</button></div>
    </form>
  </div>
}

export function SolicitudesPage(): React.JSX.Element {
  const { can } = useAuth()
  const [filters, setFilters] = useState<SolicitudFilters>(readFiltersFromUrl)
  const [response, setResponse] = useState<Awaited<ReturnType<typeof listSolicitudes>> | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [showCreate, setShowCreate] = useState(false)
  const [notice, setNotice] = useState('')

  useEffect(() => {
    const controller = new AbortController()
    updateUrl(filters)
    const timeout = window.setTimeout(() => {
      setIsLoading(true)
      setError(null)
      listSolicitudes({ q: filters.q || undefined, estado: filters.estado || undefined, medio_preferido: filters.medio_preferido || undefined, origen: filters.origen || undefined, page: filters.page, per_page: filters.per_page, sort: filters.sort, direction: filters.direction }, controller.signal)
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

  const currentItems = response?.data ?? []
  const meta = response?.meta
  const isForbidden = error instanceof ApiError && error.status === 403
  const hasActiveFilters = Boolean(filters.q || filters.estado || filters.medio_preferido || filters.origen)
  const pageWindow = useMemo(() => { if (!meta) return []; const first = Math.max(1, meta.current_page - 2); const last = Math.min(meta.last_page, first + 4); return Array.from({ length: last - first + 1 }, (_, index) => first + index) }, [meta])

  function updateFilter(field: keyof SolicitudFilters, value: string): void {
    setFilters((current) => ({ ...current, [field]: value, page: 1 }))
  }

  function handleCreated(request: SolicitudInformacion): void {
    setShowCreate(false)
    setNotice('Solicitud creada correctamente.')
    navigate(`/solicitudes/${request.id}`)
  }

  if (isForbidden) return <ErrorState title="No tienes acceso a solicitudes" message="Tu cuenta no tiene permiso para consultar este módulo." />

  return <div className="space-y-6">
    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><nav aria-label="Migas de pan" className="flex items-center gap-2 text-xs font-semibold text-[var(--app-text-muted)]"><a className="hover:text-[var(--app-accent)]" href="/dashboard">Inicio</a><span aria-hidden="true">›</span><span className="text-[var(--app-accent)]">Solicitudes</span></nav><h1 className="mt-2 text-2xl font-bold tracking-[-0.035em] text-[var(--app-text)] sm:text-3xl">Solicitudes de información</h1><p className="mt-2 text-sm text-[var(--app-text-muted)]">Da seguimiento a las personas interesadas en tus propiedades.</p></div><div className="flex flex-wrap items-center gap-3">{meta ? <p className="text-sm font-semibold text-[var(--app-text-muted)]">{meta.total} {meta.total === 1 ? 'solicitud' : 'solicitudes'}</p> : null}{can('solicitudes.crear') ? <button className="app-button-primary" onClick={() => setShowCreate(true)} type="button">+ Nueva solicitud</button> : null}</div></div>
    {notice ? <div aria-live="polite" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200">{notice}</div> : null}
    <section className="app-card p-4 sm:p-5"><div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_190px_190px_170px_auto] lg:items-end"><label><span className="app-label">Buscar</span><input className="app-input" onChange={(event) => updateFilter('q', event.target.value)} placeholder="Nombre, correo, teléfono o mensaje" value={filters.q} /></label><label><span className="app-label">Estado</span><select className="app-select w-full" onChange={(event) => updateFilter('estado', event.target.value)} value={filters.estado}><option value="">Todos</option>{statuses.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}</select></label><label><span className="app-label">Medio</span><select className="app-select w-full" onChange={(event) => updateFilter('medio_preferido', event.target.value)} value={filters.medio_preferido}><option value="">Todos</option>{mediums.map((medium) => <option key={medium.value} value={medium.value}>{medium.label}</option>)}</select></label><label><span className="app-label">Origen</span><select className="app-select w-full" onChange={(event) => updateFilter('origen', event.target.value)} value={filters.origen}><option value="">Todos</option><option value="landing_publica">Página pública</option><option value="registro_interno">Registro interno</option><option value="portal_cliente">Portal cliente</option></select></label><button className="app-button-secondary" onClick={() => setFilters(DEFAULT_FILTERS)} type="button">Limpiar</button></div></section>
    {error ? <ErrorState message={error instanceof ApiError && error.status >= 500 ? 'El servicio no respondió correctamente. Intenta nuevamente.' : undefined} onRetry={() => setFilters((current) => ({ ...current }))} /> : null}
    {isLoading && !response ? <LoadingState label="Cargando solicitudes..." /> : null}
    {!isLoading && !error && response && currentItems.length === 0 ? <EmptyState message={hasActiveFilters ? 'No encontramos solicitudes con estos filtros.' : 'Las solicitudes recibidas aparecerán aquí.'} title={hasActiveFilters ? 'Sin coincidencias' : 'Sin solicitudes'} action={can('solicitudes.crear') ? { label: 'Crear solicitud', onClick: () => setShowCreate(true) } : undefined} /> : null}
    {currentItems.length > 0 ? <section aria-label="Listado de solicitudes" className="app-card overflow-hidden"><div className="hidden overflow-x-auto lg:block"><table className="w-full text-left text-sm"><thead className="border-b border-[var(--app-border)] bg-[var(--app-surface-muted)] text-xs uppercase tracking-[0.08em] text-[var(--app-text-muted)]"><tr><th className="px-5 py-4 font-bold">Contacto</th><th className="px-5 py-4 font-bold">Inmueble</th><th className="px-5 py-4 font-bold">Estado</th><th className="px-5 py-4 font-bold">Medio</th><th className="px-5 py-4 font-bold">Recibida</th><th className="px-5 py-4 text-right font-bold">Acción</th></tr></thead><tbody className="divide-y divide-[var(--app-border)]">{currentItems.map((request) => <tr className="transition hover:bg-[var(--app-surface-muted)]" key={request.id}><td className="px-5 py-4"><p className="font-bold text-[var(--app-text)]">{personName(request)}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{request.email}</p>{request.telefono ? <p className="mt-1 text-xs text-[var(--app-text-muted)]">{request.telefono}</p> : null}</td><td className="max-w-72 px-5 py-4"><p className="truncate font-semibold text-[var(--app-text)]">{propertyLabel(request)}</p>{request.cliente ? <p className="mt-1 text-xs text-emerald-700 dark:text-emerald-300">Cliente vinculado</p> : null}</td><td className="px-5 py-4"><SolicitudStatusBadge status={request.estado} /></td><td className="px-5 py-4 text-[var(--app-text-muted)]">{getSolicitudMediumLabel(request.medio_preferido)}</td><td className="whitespace-nowrap px-5 py-4 text-[var(--app-text-muted)]">{formatDate(request.fecha_solicitud)}</td><td className="px-5 py-4 text-right"><button className="app-button-secondary px-3 py-2 text-xs" onClick={() => navigate(`/solicitudes/${request.id}`)} type="button">Ver solicitud</button></td></tr>)}</tbody></table></div><div className="space-y-3 p-4 lg:hidden">{currentItems.map((request) => <article className="rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-muted)] p-4" key={request.id}><div className="flex items-start justify-between gap-3"><div className="min-w-0"><p className="truncate font-bold text-[var(--app-text)]">{personName(request)}</p><p className="mt-1 truncate text-xs text-[var(--app-text-muted)]">{request.email}</p></div><SolicitudStatusBadge status={request.estado} /></div><p className="mt-4 text-sm font-semibold text-[var(--app-text)]">{propertyLabel(request)}</p><div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-[var(--app-text-muted)]"><span>{getSolicitudMediumLabel(request.medio_preferido)}</span><span>{formatDate(request.fecha_solicitud)}</span></div><button className="app-button-secondary mt-4 w-full" onClick={() => navigate(`/solicitudes/${request.id}`)} type="button">Ver solicitud</button></article>)}</div><div className="flex flex-col gap-4 border-t border-[var(--app-border)] px-4 py-4 text-sm sm:flex-row sm:items-center sm:justify-between"><p className="text-[var(--app-text-muted)]">Mostrando <span className="font-semibold text-[var(--app-text)]">{meta?.from ?? 0}–{meta?.to ?? 0}</span> de <span className="font-semibold text-[var(--app-text)]">{meta?.total ?? currentItems.length}</span></p><div className="flex items-center gap-3"><span className="text-[var(--app-text-muted)]">Por página</span><select aria-label="Solicitudes por página" className="app-select" onChange={(event) => updateFilter('per_page', event.target.value)} value={filters.per_page}><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></div></div></section> : null}
    {meta && meta.last_page > 1 ? <nav aria-label="Paginación de solicitudes" className="flex flex-wrap items-center justify-center gap-1"><button aria-label="Página anterior" className="app-button-secondary px-3" disabled={!response?.links.prev} onClick={() => setFilters((current) => ({ ...current, page: Math.max(1, current.page - 1) }))} type="button">Anterior</button>{pageWindow.map((page) => <button aria-current={page === meta.current_page ? 'page' : undefined} className={page === meta.current_page ? 'app-button-primary px-3' : 'app-button-secondary px-3'} key={page} onClick={() => setFilters((current) => ({ ...current, page }))} type="button">{page}</button>)}<button aria-label="Página siguiente" className="app-button-secondary px-3" disabled={!response?.links.next} onClick={() => setFilters((current) => ({ ...current, page: Math.min(meta.last_page, current.page + 1) }))} type="button">Siguiente</button></nav> : null}
    {showCreate ? <CreateSolicitudModal onClose={() => setShowCreate(false)} onCreated={handleCreated} /> : null}
  </div>
}
