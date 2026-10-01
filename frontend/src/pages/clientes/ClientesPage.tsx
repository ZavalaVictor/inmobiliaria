import { useEffect, useMemo, useState } from 'react'
import { ClienteStatusBadge } from '../../components/clientes/ClienteStatusBadge.tsx'
import { EmptyState } from '../../components/ui/EmptyState.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'
import { createCliente, listClientes } from '../../services/clientes.ts'
import { navigate } from '../../router/navigation.ts'
import type { ClienteDetail, ClienteWritePayload, EstadoCliente, TipoInteresCliente } from '../../types/clientes.ts'
import { getClienteInterestTypeLabel, getFullClientName } from '../../utils/clientes.ts'

interface ClientFilters {
  q: string
  estado_cliente: EstadoCliente | ''
  tipo_interes: TipoInteresCliente | ''
  page: number
  per_page: 15 | 25 | 50
  sort: 'created_at' | 'nombres'
  direction: 'asc' | 'desc'
}

const DEFAULT_FILTERS: ClientFilters = { q: '', estado_cliente: '', tipo_interes: '', page: 1, per_page: 15, sort: 'created_at', direction: 'desc' }
const statuses: Array<{ value: EstadoCliente; label: string }> = [{ value: 'prospecto', label: 'Prospecto' }, { value: 'cliente', label: 'Cliente' }, { value: 'inactivo', label: 'Inactivo' }]
const interestTypes: Array<{ value: TipoInteresCliente; label: string }> = [{ value: 'compra', label: 'Compra' }, { value: 'renta', label: 'Renta' }, { value: 'ambos', label: 'Compra y renta' }]

function readFiltersFromUrl(): ClientFilters {
  const params = new URLSearchParams(window.location.search)
  const estado = params.get('estado_cliente') ?? ''
  const interes = params.get('tipo_interes') ?? ''
  const perPage = Number(params.get('per_page') ?? '15')
  return {
    ...DEFAULT_FILTERS,
    q: params.get('q') ?? '',
    estado_cliente: statuses.some((item) => item.value === estado) ? estado as EstadoCliente : '',
    tipo_interes: interestTypes.some((item) => item.value === interes) ? interes as TipoInteresCliente : '',
    page: Math.max(1, Number(params.get('page') ?? '1') || 1),
    per_page: perPage === 25 || perPage === 50 ? perPage : 15,
    sort: params.get('sort') === 'nombres' ? 'nombres' : 'created_at',
    direction: params.get('direction') === 'asc' ? 'asc' : 'desc',
  }
}

function updateUrl(filters: ClientFilters): void {
  const params = new URLSearchParams()
  if (filters.q) params.set('q', filters.q)
  if (filters.estado_cliente) params.set('estado_cliente', filters.estado_cliente)
  if (filters.tipo_interes) params.set('tipo_interes', filters.tipo_interes)
  if (filters.page > 1) params.set('page', String(filters.page))
  if (filters.per_page !== 15) params.set('per_page', String(filters.per_page))
  if (filters.sort !== 'created_at') params.set('sort', filters.sort)
  if (filters.direction !== 'desc') params.set('direction', filters.direction)
  const query = params.toString()
  window.history.replaceState({}, '', '/clientes' + (query ? '?' + query : ''))
}

function money(value: string | number | null): string {
  if (value === null || value === '') return '—'
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(Number(value))
}

function FieldError({ errors, field }: { errors: Record<string, string[]>; field: string }): React.JSX.Element | null {
  return errors[field]?.[0] ? <p className="mt-1 text-xs font-semibold text-[var(--app-danger)]">{errors[field][0]}</p> : null
}

function CreateClientModal({ onClose, onCreated }: { onClose: () => void; onCreated: (client: ClienteDetail) => void }): React.JSX.Element {
  const [values, setValues] = useState<ClienteWritePayload>({ nombres: '', apellido_paterno: '', apellido_materno: '', email: '', telefono: '', tipo_interes: 'compra', estado_cliente: 'prospecto', presupuesto_min: '', presupuesto_max: '', preferencias: '' })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [isSubmitting, setIsSubmitting] = useState(false)
  const setValue = (field: keyof ClienteWritePayload, value: string): void => {
    setValues((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: [] }))
  }
  async function submit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault()
    setIsSubmitting(true)
    setErrors({})
    try {
      const created = await createCliente({ ...values, email: values.email || null, telefono: values.telefono || null, apellido_materno: values.apellido_materno || null, presupuesto_min: values.presupuesto_min || null, presupuesto_max: values.presupuesto_max || null, preferencias: values.preferencias || null })
      onCreated(created)
    } catch (error: unknown) {
      setErrors(getValidationErrors(error instanceof ApiError ? error.payload : null))
    } finally {
      setIsSubmitting(false)
    }
  }
  return <div aria-modal="true" className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-4" role="dialog"><form className="max-h-[min(820px,calc(100vh-2rem))] w-full max-w-3xl overflow-y-auto rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-6 shadow-2xl" onSubmit={(event) => void submit(event)}><div className="flex items-start justify-between gap-4"><div><p className="text-xs font-bold uppercase tracking-[0.14em] text-[var(--app-accent)]">CRM</p><h2 className="mt-2 text-2xl font-bold text-[var(--app-text)]">Nuevo cliente</h2></div><button aria-label="Cerrar" className="app-icon-button" onClick={onClose} type="button">×</button></div><div className="mt-6 grid gap-4 sm:grid-cols-2"><label><span className="app-label">Nombres</span><input className="app-input" onChange={(event) => setValue('nombres', event.target.value)} required value={values.nombres ?? ''} /><FieldError errors={errors} field="nombres" /></label><label><span className="app-label">Apellido paterno</span><input className="app-input" onChange={(event) => setValue('apellido_paterno', event.target.value)} required value={values.apellido_paterno ?? ''} /><FieldError errors={errors} field="apellido_paterno" /></label><label><span className="app-label">Apellido materno</span><input className="app-input" onChange={(event) => setValue('apellido_materno', event.target.value)} value={values.apellido_materno ?? ''} /></label><label><span className="app-label">Correo</span><input className="app-input" onChange={(event) => setValue('email', event.target.value)} type="email" value={values.email ?? ''} /></label><label><span className="app-label">Teléfono</span><input className="app-input" onChange={(event) => setValue('telefono', event.target.value)} value={values.telefono ?? ''} /></label><label><span className="app-label">Estado</span><select className="app-select w-full" onChange={(event) => setValue('estado_cliente', event.target.value)} value={values.estado_cliente ?? ''}><option value="prospecto">Prospecto</option><option value="cliente">Cliente</option><option value="inactivo">Inactivo</option></select></label><label><span className="app-label">Interés</span><select className="app-select w-full" onChange={(event) => setValue('tipo_interes', event.target.value)} value={values.tipo_interes ?? ''}><option value="">Sin definir</option>{interestTypes.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label><label><span className="app-label">Presupuesto mínimo</span><input className="app-input" min="0" onChange={(event) => setValue('presupuesto_min', event.target.value)} type="number" value={values.presupuesto_min ?? ''} /></label><label><span className="app-label">Presupuesto máximo</span><input className="app-input" min="0" onChange={(event) => setValue('presupuesto_max', event.target.value)} type="number" value={values.presupuesto_max ?? ''} /></label><label className="sm:col-span-2"><span className="app-label">Preferencias</span><textarea className="app-input min-h-24 resize-y" onChange={(event) => setValue('preferencias', event.target.value)} value={values.preferencias ?? ''} /></label></div><div className="mt-7 flex justify-end gap-3"><button className="app-button-secondary" onClick={onClose} type="button">Cancelar</button><button className="app-button-primary" disabled={isSubmitting} type="submit">{isSubmitting ? 'Guardando…' : 'Guardar cliente'}</button></div></form></div>
}

export function ClientesPage(): React.JSX.Element {
  const { can } = useAuth()
  const [filters, setFilters] = useState<ClientFilters>(readFiltersFromUrl)
  const [response, setResponse] = useState<Awaited<ReturnType<typeof listClientes>> | null>(null)
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
      listClientes({ q: filters.q || undefined, estado_cliente: filters.estado_cliente || undefined, tipo_interes: filters.tipo_interes || undefined, page: filters.page, per_page: filters.per_page, sort: filters.sort, direction: filters.direction }, controller.signal).then(setResponse).catch((nextError: unknown) => { if (!controller.signal.aborted) { setResponse(null); setError(nextError) } }).finally(() => { if (!controller.signal.aborted) setIsLoading(false) })
    }, filters.q ? 350 : 0)
    return () => { window.clearTimeout(timeout); controller.abort() }
  }, [filters])
  useEffect(() => {
    const handlePopState = (): void => setFilters(readFiltersFromUrl())
    window.addEventListener('popstate', handlePopState)
    return () => window.removeEventListener('popstate', handlePopState)
  }, [])
  const clients = response?.data ?? []
  const meta = response?.meta
  const isForbidden = error instanceof ApiError && error.status === 403
  const hasFilters = Boolean(filters.q || filters.estado_cliente || filters.tipo_interes)
  const pageWindow = useMemo(() => { if (!meta) return []; const first = Math.max(1, meta.current_page - 2); const last = Math.min(meta.last_page, first + 4); return Array.from({ length: last - first + 1 }, (_, index) => first + index) }, [meta])
  const updateFilter = (field: keyof ClientFilters, value: string): void => setFilters((current) => ({ ...current, [field]: value, page: 1 }))
  if (isForbidden) return <ErrorState title="No tienes acceso a clientes" message="Tu cuenta no tiene permiso para consultar este módulo." />
  return <div className="space-y-6"><header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><nav aria-label="Migas de pan" className="flex items-center gap-2 text-xs font-semibold text-[var(--app-text-muted)]"><a className="hover:text-[var(--app-accent)]" href="/dashboard">Inicio</a><span aria-hidden="true">›</span><span className="text-[var(--app-accent)]">Clientes</span></nav><h1 className="mt-2 text-2xl font-bold text-[var(--app-text)] sm:text-3xl">Clientes</h1><p className="mt-2 text-sm text-[var(--app-text-muted)]">Administra prospectos, clientes y el seguimiento comercial.</p></div><div className="flex flex-wrap items-center gap-3">{meta ? <p className="text-sm font-semibold text-[var(--app-text-muted)]">{meta.total} {meta.total === 1 ? 'cliente' : 'clientes'}</p> : null}{can('clientes.crear') ? <button className="app-button-primary" onClick={() => setShowCreate(true)} type="button">+ Nuevo cliente</button> : null}</div></header>{notice ? <div aria-live="polite" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200">{notice}</div> : null}<section className="app-card p-4"><div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_190px_190px_auto] lg:items-end"><label><span className="app-label">Buscar</span><input className="app-input" onChange={(event) => updateFilter('q', event.target.value)} placeholder="Nombre, correo o teléfono" value={filters.q} /></label><label><span className="app-label">Estado</span><select className="app-select w-full" onChange={(event) => updateFilter('estado_cliente', event.target.value)} value={filters.estado_cliente}><option value="">Todos</option>{statuses.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label><label><span className="app-label">Interés</span><select className="app-select w-full" onChange={(event) => updateFilter('tipo_interes', event.target.value)} value={filters.tipo_interes}><option value="">Todos</option>{interestTypes.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label><button className="app-button-secondary" onClick={() => setFilters(DEFAULT_FILTERS)} type="button">Limpiar</button></div></section>{error ? <ErrorState onRetry={() => setFilters((current) => ({ ...current }))} /> : null}{isLoading && !response ? <LoadingState label="Cargando clientes..." /> : null}{!isLoading && !error && response && clients.length === 0 ? <EmptyState message={hasFilters ? 'No encontramos clientes con estos filtros.' : 'Los clientes registrados aparecerán aquí.'} title={hasFilters ? 'Sin coincidencias' : 'Sin clientes'} action={can('clientes.crear') ? { label: 'Crear cliente', onClick: () => setShowCreate(true) } : undefined} /> : null}{clients.length > 0 ? <section className="app-card overflow-hidden"><div className="hidden overflow-x-auto lg:block"><table className="w-full text-left text-sm"><thead className="border-b border-[var(--app-border)] bg-[var(--app-surface-muted)] text-xs uppercase text-[var(--app-text-muted)]"><tr><th className="px-5 py-4">Cliente</th><th className="px-5 py-4">Contacto</th><th className="px-5 py-4">Interés</th><th className="px-5 py-4">Presupuesto</th><th className="px-5 py-4">Estado</th><th className="px-5 py-4 text-right">Acción</th></tr></thead><tbody className="divide-y divide-[var(--app-border)]">{clients.map((client) => <tr className="hover:bg-[var(--app-surface-muted)]" key={client.id}><td className="px-5 py-4"><p className="font-bold text-[var(--app-text)]">{getFullClientName(client)}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">#{client.id}</p></td><td className="px-5 py-4"><p>{client.email || 'Sin correo'}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{client.telefono || 'Sin teléfono'}</p></td><td className="px-5 py-4 text-[var(--app-text-muted)]">{getClienteInterestTypeLabel(client.tipo_interes)}</td><td className="px-5 py-4 text-[var(--app-text-muted)]">{money(client.presupuesto_min)} – {money(client.presupuesto_max)}</td><td className="px-5 py-4"><ClienteStatusBadge status={client.estado_cliente} /></td><td className="px-5 py-4 text-right"><button className="app-button-secondary px-3 py-2 text-xs" onClick={() => navigate('/clientes/' + client.id)} type="button">Ver ficha</button></td></tr>)}</tbody></table></div><div className="space-y-3 p-4 lg:hidden">{clients.map((client) => <article className="rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-muted)] p-4" key={client.id}><div className="flex items-start justify-between gap-3"><div className="min-w-0"><p className="truncate font-bold text-[var(--app-text)]">{getFullClientName(client)}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{client.email || 'Sin correo'}</p></div><ClienteStatusBadge status={client.estado_cliente} /></div><p className="mt-4 text-sm text-[var(--app-text-muted)]">{getClienteInterestTypeLabel(client.tipo_interes)} · {money(client.presupuesto_max)}</p><button className="app-button-secondary mt-4 w-full" onClick={() => navigate('/clientes/' + client.id)} type="button">Ver ficha</button></article>)}</div><div className="flex flex-col gap-4 border-t border-[var(--app-border)] p-4 text-sm sm:flex-row sm:items-center sm:justify-between"><p className="text-[var(--app-text-muted)]">Mostrando {meta?.from ?? 0}–{meta?.to ?? 0} de {meta?.total ?? clients.length}</p><div className="flex items-center gap-3"><span className="text-[var(--app-text-muted)]">Por página</span><select aria-label="Clientes por página" className="app-select" onChange={(event) => updateFilter('per_page', event.target.value)} value={filters.per_page}><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></div></div></section> : null}{meta && meta.last_page > 1 ? <nav aria-label="Paginación de clientes" className="flex flex-wrap justify-center gap-1"><button className="app-button-secondary px-3" disabled={!response?.links.prev} onClick={() => setFilters((current) => ({ ...current, page: Math.max(1, current.page - 1) }))} type="button">Anterior</button>{pageWindow.map((page) => <button aria-current={page === meta.current_page ? 'page' : undefined} className={page === meta.current_page ? 'app-button-primary px-3' : 'app-button-secondary px-3'} key={page} onClick={() => setFilters((current) => ({ ...current, page }))} type="button">{page}</button>)}<button className="app-button-secondary px-3" disabled={!response?.links.next} onClick={() => setFilters((current) => ({ ...current, page: Math.min(meta.last_page, current.page + 1) }))} type="button">Siguiente</button></nav> : null}{showCreate ? <CreateClientModal onClose={() => setShowCreate(false)} onCreated={(client) => { setShowCreate(false); setNotice('Cliente creado correctamente.'); navigate('/clientes/' + client.id) }} /> : null}</div>
}
