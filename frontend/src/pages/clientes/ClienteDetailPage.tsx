import { useCallback, useEffect, useState } from 'react'
import { ClienteStatusBadge } from '../../components/clientes/ClienteStatusBadge.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'
import { listAgentesActivos } from '../../services/agentes.ts'
import { createClienteInteres, createClienteInteraccion, deleteCliente, deleteClienteAgente, deleteClienteInteres, deleteClienteInteraccion, disableClientePortal, enableClientePortal, getCliente, listClienteAgentes, listClienteInteracciones, listClienteIntereses, listClienteSolicitudes, assignClienteAgente, setPrincipalClienteAgente, updateCliente, updateClienteInteres, updateClienteInteraccion } from '../../services/clientes.ts'
import { listInmuebles } from '../../services/inmuebles.ts'
import { navigate } from '../../router/navigation.ts'
import type { AgenteListItem } from '../../types/agentes.ts'
import type { InmuebleListItem } from '../../types/inmuebles.ts'
import type { ClienteAgentAssignment, ClienteDetail, ClienteInteraction, ClienteInteractionPayload, ClienteInteres, ClienteInterestPayload, ClienteRequestHistory, ClienteWritePayload, EstadoCliente, EstadoInteres, NivelInteres, TipoInteraccion, TipoInteresCliente } from '../../types/clientes.ts'
import { formatClientDate, getFullClientName, getInterestLevelLabel, getInterestStatusLabel, getInteractionTypeLabel } from '../../utils/clientes.ts'

const statuses: Array<{ value: EstadoCliente; label: string }> = [{ value: 'prospecto', label: 'Prospecto' }, { value: 'cliente', label: 'Cliente' }, { value: 'inactivo', label: 'Inactivo' }]
const interestTypes: Array<{ value: TipoInteresCliente; label: string }> = [{ value: 'compra', label: 'Compra' }, { value: 'renta', label: 'Renta' }, { value: 'ambos', label: 'Compra y renta' }]
const interestLevels: Array<{ value: NivelInteres; label: string }> = [{ value: 'bajo', label: 'Bajo' }, { value: 'medio', label: 'Medio' }, { value: 'alto', label: 'Alto' }]
const interestStatuses: Array<{ value: EstadoInteres; label: string }> = [{ value: 'activo', label: 'Activo' }, { value: 'descartado', label: 'Descartado' }, { value: 'convertido', label: 'Convertido' }]
const interactionTypes: Array<{ value: TipoInteraccion; label: string }> = [{ value: 'llamada', label: 'Llamada' }, { value: 'correo', label: 'Correo' }, { value: 'whatsapp', label: 'WhatsApp' }, { value: 'reunion', label: 'Reunión' }, { value: 'nota', label: 'Nota' }, { value: 'seguimiento', label: 'Seguimiento' }]

function formatDateTimeLocal(value: string | null): string {
  if (!value) return ''
  const date = new Date(value)
  const pad = (part: number): string => String(part).padStart(2, '0')
  return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes())
}

function toBackendDateTime(value: string): string | null {
  return value ? value.replace('T', ' ') + ':00' : null
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

function FieldError({ errors, field }: { errors: Record<string, string[]>; field: string }): React.JSX.Element | null {
  return errors[field]?.[0] ? <p className="mt-1 text-xs font-semibold text-[var(--app-danger)]">{errors[field][0]}</p> : null
}

function RequestStatus({ value }: { value: ClienteRequestHistory['estado'] }): React.JSX.Element {
  const labels = { nueva: 'Nueva', en_atencion: 'En atención', atendida: 'Atendida', descartada: 'Descartada' }
  return <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{labels[value]}</span>
}

export function ClienteDetailPage({ id }: { id: number }): React.JSX.Element {
  const { can } = useAuth()
  const [client, setClient] = useState<ClienteDetail | null>(null)
  const [interests, setInterests] = useState<ClienteInteres[]>([])
  const [assignments, setAssignments] = useState<ClienteAgentAssignment[]>([])
  const [interactions, setInteractions] = useState<ClienteInteraction[]>([])
  const [requests, setRequests] = useState<ClienteRequestHistory[]>([])
  const [properties, setProperties] = useState<InmuebleListItem[]>([])
  const [agents, setAgents] = useState<AgenteListItem[]>([])
  const [error, setError] = useState<unknown>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [notice, setNotice] = useState('')
  const [formErrors, setFormErrors] = useState<Record<string, string[]>>({})
  const [isSaving, setIsSaving] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)
  const [isPortalLoading, setIsPortalLoading] = useState(false)
  const [clientForm, setClientForm] = useState<ClienteWritePayload>({})
  const [interestForm, setInterestForm] = useState<ClienteInterestPayload>({ inmueble_id: undefined, nivel_interes: null, estado: 'activo', notas: '' })
  const [editingInterest, setEditingInterest] = useState<number | null>(null)
  const [interactionForm, setInteractionForm] = useState<ClienteInteractionPayload>({ tipo: 'llamada', descripcion: '', resultado: '', fecha_interaccion: new Date().toISOString().slice(0, 16), proxima_accion: '', fecha_proxima_accion: null })
  const [editingInteraction, setEditingInteraction] = useState<number | null>(null)
  const [assignmentAgentId, setAssignmentAgentId] = useState('')
  const [isInterestFormOpen, setIsInterestFormOpen] = useState(false)
  const [isInteractionFormOpen, setIsInteractionFormOpen] = useState(false)
  const [isAssignmentLoading, setIsAssignmentLoading] = useState(false)

  const canUpdate = can('clientes.actualizar')
  const canDelete = can('clientes.eliminar')
  const canManageInterest = can('intereses.crear') || can('intereses.actualizar')
  const canDeleteInterest = can('intereses.eliminar')
  const canAssign = can('asignaciones_cliente_agente.crear')
  const canManageInteractions = can('interacciones.crear') || can('interacciones.actualizar')
  const canDeleteInteractions = can('interacciones.eliminar')
  const canManagePortal = can('clientes.portal.gestionar')
  const canViewInterests = can('intereses.ver')
  const canViewAssignments = can('asignaciones_cliente_agente.ver')
  const canViewInteractions = can('interacciones.ver')
  const canViewRequests = can('solicitudes.ver')

  const syncClientForm = useCallback((nextClient: ClienteDetail): void => {
    setClientForm({ nombres: nextClient.nombres, apellido_paterno: nextClient.apellido_paterno, apellido_materno: nextClient.apellido_materno ?? '', email: nextClient.email ?? '', telefono: nextClient.telefono ?? '', tipo_interes: nextClient.tipo_interes, presupuesto_min: nextClient.presupuesto_min === null ? '' : String(nextClient.presupuesto_min), presupuesto_max: nextClient.presupuesto_max === null ? '' : String(nextClient.presupuesto_max), preferencias: nextClient.preferencias ?? '', estado_cliente: nextClient.estado_cliente })
  }, [])

  const loadData = useCallback(async (signal?: AbortSignal): Promise<void> => {
    const [nextClient, interestResponse, assignmentResponse, interactionResponse, requestResponse] = await Promise.all([
      getCliente(id, signal),
      canViewInterests ? listClienteIntereses(id, signal) : Promise.resolve({ data: [] as ClienteInteres[] }),
      canViewAssignments ? listClienteAgentes(id, signal) : Promise.resolve({ data: [] as ClienteAgentAssignment[] }),
      canViewInteractions ? listClienteInteracciones(id, signal) : Promise.resolve({ data: [] as ClienteInteraction[], links: { first: null, last: null, prev: null, next: null }, meta: { current_page: 1, from: null, last_page: 1, path: '', per_page: 100, to: null, total: 0 } }),
      canViewRequests ? listClienteSolicitudes(id, signal) : Promise.resolve({ data: [] as ClienteRequestHistory[], links: { first: null, last: null, prev: null, next: null }, meta: { current_page: 1, from: null, last_page: 1, path: '', per_page: 100, to: null, total: 0 } }),
    ])
    setClient(nextClient)
    syncClientForm(nextClient)
    setInterests(interestResponse.data)
    setAssignments(assignmentResponse.data)
    setInteractions(interactionResponse.data)
    setRequests(requestResponse.data)
  }, [canViewAssignments, canViewInteractions, canViewInterests, canViewRequests, id, syncClientForm])

  useEffect(() => {
    const controller = new AbortController()
    loadData(controller.signal)
      .catch((nextError: unknown) => { if (!isAbortError(nextError)) setError(nextError) })
      .finally(() => { if (!controller.signal.aborted) setIsLoading(false) })
    return () => controller.abort()
  }, [loadData])

  useEffect(() => {
    const controller = new AbortController()
    Promise.all([
      canViewInterests ? listInmuebles({ page: 1, per_page: 100, sort: 'created_at', direction: 'desc' }, controller.signal) : Promise.resolve(null),
      canAssign ? listAgentesActivos(controller.signal) : Promise.resolve(null),
    ]).then(([propertyResponse, agentResponse]) => {
      if (propertyResponse) setProperties(propertyResponse.data)
      if (agentResponse) setAgents(agentResponse.data)
    }).catch((nextError: unknown) => { if (!isAbortError(nextError)) setNotice('No pudimos cargar todas las opciones relacionadas.') })
    return () => controller.abort()
  }, [canAssign, canViewInterests])

  async function saveClient(): Promise<void> {
    if (!client) return
    setIsSaving(true)
    setFormErrors({})
    setNotice('')
    try {
      const updated = await updateCliente(id, { ...clientForm, email: clientForm.email || null, telefono: clientForm.telefono || null, apellido_materno: clientForm.apellido_materno || null, presupuesto_min: clientForm.presupuesto_min || null, presupuesto_max: clientForm.presupuesto_max || null, preferencias: clientForm.preferencias || null })
      setClient(updated)
      syncClientForm(updated)
      setNotice('Cliente actualizado correctamente.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    } finally {
      setIsSaving(false)
    }
  }

  async function removeClient(): Promise<void> {
    if (!window.confirm('¿Eliminar este cliente? Se conservará como eliminación lógica.')) return
    setIsDeleting(true)
    try {
      await deleteCliente(id)
      navigate('/clientes')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
      setIsDeleting(false)
    }
  }

  async function saveInterest(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault()
    setFormErrors({})
    try {
      const payload = { ...interestForm, inmueble_id: interestForm.inmueble_id ? Number(interestForm.inmueble_id) : undefined }
      const saved = editingInterest ? await updateClienteInteres(id, editingInterest, payload) : await createClienteInteres(id, payload)
      setInterests((current) => editingInterest ? current.map((item) => item.id === saved.id ? saved : item) : [saved, ...current])
      setIsInterestFormOpen(false)
      setEditingInterest(null)
      setNotice(editingInterest ? 'Interés actualizado correctamente.' : 'Interés agregado correctamente.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    }
  }

  function editInterest(interest: ClienteInteres): void {
    setEditingInterest(interest.id)
    setInterestForm({ nivel_interes: interest.nivel_interes, estado: interest.estado, notas: interest.notas })
    setFormErrors({})
    setIsInterestFormOpen(true)
  }

  async function removeInterest(interest: ClienteInteres): Promise<void> {
    if (!window.confirm('¿Quitar este interés del cliente?')) return
    try {
      await deleteClienteInteres(id, interest.id)
      setInterests((current) => current.filter((item) => item.id !== interest.id))
      setNotice('Interés eliminado correctamente.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    }
  }

  async function addAssignment(): Promise<void> {
    if (!assignmentAgentId) return
    setIsAssignmentLoading(true)
    try {
      const created = await assignClienteAgente(id, { agente_id: Number(assignmentAgentId) })
      setAssignments((current) => [...current.map((item) => ({ ...item, es_principal: created.es_principal ? false : item.es_principal })), created])
      setAssignmentAgentId('')
      setNotice('Agente asignado correctamente.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    } finally {
      setIsAssignmentLoading(false)
    }
  }

  async function makePrincipal(assignment: ClienteAgentAssignment): Promise<void> {
    try {
      const updated = await setPrincipalClienteAgente(id, assignment.id)
      setAssignments((current) => current.map((item) => ({ ...item, es_principal: item.id === updated.id })))
      setNotice('Agente principal actualizado.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    }
  }

  async function removeAssignment(assignment: ClienteAgentAssignment): Promise<void> {
    if (!window.confirm('¿Desasignar este agente?')) return
    try {
      await deleteClienteAgente(id, assignment.id)
      setAssignments((current) => current.filter((item) => item.id !== assignment.id))
      setNotice('Agente desasignado correctamente.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    }
  }

  async function saveInteraction(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault()
    setFormErrors({})
    try {
      const payload: ClienteInteractionPayload = { ...interactionForm, fecha_interaccion: interactionForm.fecha_interaccion ? toBackendDateTime(interactionForm.fecha_interaccion) ?? undefined : undefined, fecha_proxima_accion: interactionForm.fecha_proxima_accion ? toBackendDateTime(interactionForm.fecha_proxima_accion) : null }
      const saved = editingInteraction ? await updateClienteInteraccion(id, editingInteraction, payload) : await createClienteInteraccion(id, payload)
      setInteractions((current) => editingInteraction ? current.map((item) => item.id === saved.id ? saved : item) : [saved, ...current])
      setIsInteractionFormOpen(false)
      setEditingInteraction(null)
      setNotice(editingInteraction ? 'Interacción actualizada correctamente.' : 'Interacción registrada correctamente.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    }
  }

  function editInteraction(interaction: ClienteInteraction): void {
    setEditingInteraction(interaction.id)
    setInteractionForm({ tipo: interaction.tipo, descripcion: interaction.descripcion, resultado: interaction.resultado ?? '', fecha_interaccion: formatDateTimeLocal(interaction.fecha_interaccion), proxima_accion: interaction.proxima_accion ?? '', fecha_proxima_accion: formatDateTimeLocal(interaction.fecha_proxima_accion) || null })
    setFormErrors({})
    setIsInteractionFormOpen(true)
  }

  async function removeInteraction(interaction: ClienteInteraction): Promise<void> {
    if (!window.confirm('¿Eliminar esta interacción?')) return
    try {
      await deleteClienteInteraccion(id, interaction.id)
      setInteractions((current) => current.filter((item) => item.id !== interaction.id))
      setNotice('Interacción eliminada correctamente.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    }
  }

  async function togglePortal(): Promise<void> {
    if (!client) return
    setIsPortalLoading(true)
    try {
      if (client.portal?.habilitado) await disableClientePortal(id)
      else await enableClientePortal(id)
      const refreshed = await getCliente(id)
      setClient(refreshed)
      syncClientForm(refreshed)
      setNotice(refreshed.portal?.habilitado ? 'Acceso al portal habilitado.' : 'Acceso al portal deshabilitado.')
    } catch (nextError: unknown) {
      setFormErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    } finally {
      setIsPortalLoading(false)
    }
  }

  if (isLoading) return <LoadingState label="Cargando ficha del cliente..." />
  if (error instanceof ApiError && error.status === 403) return <ErrorState title="No tienes acceso a este cliente" message="El cliente no está dentro del alcance de tu cuenta." onRetry={() => window.location.reload()} />
  if (error || !client) return <ErrorState title="No pudimos cargar el cliente" onRetry={() => window.location.reload()} />

  return <div className="space-y-6">
    <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><button className="text-sm font-bold text-[var(--app-accent)] hover:underline" onClick={() => navigate('/clientes')} type="button">← Volver a clientes</button><p className="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-[var(--app-accent)]">Ficha de cliente #{client.id}</p><h1 className="mt-2 text-2xl font-bold tracking-[-0.035em] text-[var(--app-text)] sm:text-3xl">{getFullClientName(client)}</h1><p className="mt-2 text-sm text-[var(--app-text-muted)]">{client.email || 'Sin correo'} · {client.telefono || 'Sin teléfono'}</p></div><div className="flex flex-wrap gap-3"><ClienteStatusBadge status={client.estado_cliente} />{canDelete ? <button className="app-button-secondary text-[var(--app-danger)]" disabled={isDeleting} onClick={() => void removeClient()} type="button">{isDeleting ? 'Eliminando…' : 'Eliminar'}</button> : null}</div></header>
    {notice ? <div aria-live="polite" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200">{notice}</div> : null}
    {formErrors.form ? <div className="rounded-xl bg-[var(--app-danger-surface)] p-4 text-sm font-semibold text-[var(--app-danger)]">{formErrors.form[0]}</div> : null}
    <section className="app-card p-5 sm:p-6"><div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"><div><h2 className="text-lg font-bold text-[var(--app-text)]">Datos del cliente</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">Información de contacto, perfil comercial y presupuesto.</p></div>{canUpdate ? <button className="app-button-primary" disabled={isSaving} onClick={() => void saveClient()} type="button">{isSaving ? 'Guardando…' : 'Guardar cambios'}</button> : null}</div><div className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><label><span className="app-label">Nombres</span><input className="app-input" disabled={!canUpdate} onChange={(event) => setClientForm((current) => ({ ...current, nombres: event.target.value }))} value={clientForm.nombres ?? ''} /><FieldError errors={formErrors} field="nombres" /></label><label><span className="app-label">Apellido paterno</span><input className="app-input" disabled={!canUpdate} onChange={(event) => setClientForm((current) => ({ ...current, apellido_paterno: event.target.value }))} value={clientForm.apellido_paterno ?? ''} /><FieldError errors={formErrors} field="apellido_paterno" /></label><label><span className="app-label">Apellido materno</span><input className="app-input" disabled={!canUpdate} onChange={(event) => setClientForm((current) => ({ ...current, apellido_materno: event.target.value }))} value={clientForm.apellido_materno ?? ''} /></label><label><span className="app-label">Correo</span><input className="app-input" disabled={!canUpdate} onChange={(event) => setClientForm((current) => ({ ...current, email: event.target.value }))} type="email" value={clientForm.email ?? ''} /><FieldError errors={formErrors} field="email" /></label><label><span className="app-label">Teléfono</span><input className="app-input" disabled={!canUpdate} onChange={(event) => setClientForm((current) => ({ ...current, telefono: event.target.value }))} value={clientForm.telefono ?? ''} /></label><label><span className="app-label">Estado</span><select className="app-select w-full" disabled={!canUpdate} onChange={(event) => setClientForm((current) => ({ ...current, estado_cliente: event.target.value as EstadoCliente }))} value={clientForm.estado_cliente ?? ''}>{statuses.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label><label><span className="app-label">Interés principal</span><select className="app-select w-full" disabled={!canUpdate} onChange={(event) => setClientForm((current) => ({ ...current, tipo_interes: event.target.value as TipoInteresCliente }))} value={clientForm.tipo_interes ?? ''}><option value="">Sin definir</option>{interestTypes.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label><label><span className="app-label">Presupuesto mínimo</span><input className="app-input" disabled={!canUpdate} min="0" onChange={(event) => setClientForm((current) => ({ ...current, presupuesto_min: event.target.value }))} step="0.01" type="number" value={clientForm.presupuesto_min ?? ''} /></label><label><span className="app-label">Presupuesto máximo</span><input className="app-input" disabled={!canUpdate} min="0" onChange={(event) => setClientForm((current) => ({ ...current, presupuesto_max: event.target.value }))} step="0.01" type="number" value={clientForm.presupuesto_max ?? ''} /><FieldError errors={formErrors} field="presupuesto_max" /></label><label className="sm:col-span-2 lg:col-span-3"><span className="app-label">Preferencias</span><textarea className="app-input min-h-24 resize-y" disabled={!canUpdate} onChange={(event) => setClientForm((current) => ({ ...current, preferencias: event.target.value }))} value={clientForm.preferencias ?? ''} /></label></div></section>
    <div className="grid gap-6 xl:grid-cols-2">
      <section className="app-card p-5 sm:p-6"><div className="flex items-start justify-between gap-3"><div><h2 className="text-lg font-bold text-[var(--app-text)]">Inmuebles de interés</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">{interests.length} registrados</p></div>{canManageInterest ? <button className="app-button-secondary px-3 py-2 text-xs" onClick={() => { setEditingInterest(null); setInterestForm({ inmueble_id: undefined, nivel_interes: null, estado: 'activo', notas: '' }); setFormErrors({}); setIsInterestFormOpen(true) }} type="button">+ Agregar</button> : null}</div><div className="mt-5 space-y-3">{interests.length === 0 ? <p className="rounded-xl bg-[var(--app-surface-muted)] p-4 text-sm text-[var(--app-text-muted)]">Todavía no hay inmuebles de interés.</p> : interests.map((interest) => <article className="rounded-xl border border-[var(--app-border)] p-4" key={interest.id}><div className="flex items-start justify-between gap-3"><div><p className="text-xs font-bold uppercase tracking-[0.1em] text-[var(--app-accent)]">{interest.inmueble?.codigo ?? 'Inmueble'}</p><p className="mt-1 font-bold text-[var(--app-text)]">{interest.inmueble?.titulo ?? 'Sin información del inmueble'}</p></div><span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{getInterestStatusLabel(interest.estado)}</span></div><p className="mt-3 text-sm text-[var(--app-text-muted)]">Interés: {getInterestLevelLabel(interest.nivel_interes)}</p>{interest.notas ? <p className="mt-2 whitespace-pre-wrap text-sm text-[var(--app-text)]">{interest.notas}</p> : null}<div className="mt-4 flex flex-wrap gap-2">{canManageInterest ? <button className="app-button-secondary px-3 py-2 text-xs" onClick={() => editInterest(interest)} type="button">Editar</button> : null}{canDeleteInterest ? <button className="app-button-secondary px-3 py-2 text-xs text-[var(--app-danger)]" onClick={() => void removeInterest(interest)} type="button">Quitar</button> : null}</div></article>)}</div></section>
      <section className="app-card p-5 sm:p-6"><div className="flex items-start justify-between gap-3"><div><h2 className="text-lg font-bold text-[var(--app-text)]">Agentes asignados</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">{assignments.length} asignados</p></div></div>{canAssign ? <div className="mt-5 flex flex-col gap-2 sm:flex-row"><select className="app-select w-full" onChange={(event) => setAssignmentAgentId(event.target.value)} value={assignmentAgentId}><option value="">Selecciona un agente</option>{agents.filter((agent) => !assignments.some((assignment) => assignment.agente_id === agent.id)).map((agent) => <option key={agent.id} value={agent.id}>{getFullClientName(agent.user)} · {agent.numero_empleado}</option>)}</select><button className="app-button-primary whitespace-nowrap" disabled={!assignmentAgentId || isAssignmentLoading} onClick={() => void addAssignment()} type="button">{isAssignmentLoading ? 'Asignando…' : 'Asignar'}</button></div> : null}<div className="mt-5 space-y-3">{assignments.length === 0 ? <p className="rounded-xl bg-[var(--app-surface-muted)] p-4 text-sm text-[var(--app-text-muted)]">No hay agentes asignados.</p> : assignments.map((assignment) => <article className="flex flex-col justify-between gap-3 rounded-xl border border-[var(--app-border)] p-4 sm:flex-row sm:items-center" key={assignment.id}><div><p className="font-bold text-[var(--app-text)]">{assignment.agente.user ? getFullClientName(assignment.agente.user) : 'Agente sin usuario'}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{assignment.agente.numero_empleado}{assignment.es_principal ? ' · Principal' : ''}</p></div><div className="flex flex-wrap gap-2">{can('asignaciones_cliente_agente.actualizar') && !assignment.es_principal ? <button className="app-button-secondary px-3 py-2 text-xs" onClick={() => void makePrincipal(assignment)} type="button">Hacer principal</button> : null}{can('asignaciones_cliente_agente.eliminar') ? <button className="app-button-secondary px-3 py-2 text-xs text-[var(--app-danger)]" onClick={() => void removeAssignment(assignment)} type="button">Desasignar</button> : null}</div></article>)}</div></section>
    </div>
    {canManagePortal ? <section className="app-card flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center sm:p-6"><div><h2 className="text-lg font-bold text-[var(--app-text)]">Portal del cliente</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">{client.portal?.habilitado ? 'El acceso está habilitado.' : 'El cliente no tiene acceso activo al portal.'}</p></div><button className={client.portal?.habilitado ? 'app-button-secondary' : 'app-button-primary'} disabled={isPortalLoading} onClick={() => void togglePortal()} type="button">{isPortalLoading ? 'Procesando…' : client.portal?.habilitado ? 'Deshabilitar acceso' : 'Habilitar portal'}</button></section> : null}
    <div className="grid gap-6 xl:grid-cols-2">
      <section className="app-card p-5 sm:p-6"><div className="flex items-start justify-between gap-3"><div><h2 className="text-lg font-bold text-[var(--app-text)]">Historial de interacciones</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">{interactions.length} registradas</p></div>{canManageInteractions ? <button className="app-button-secondary px-3 py-2 text-xs" onClick={() => { setEditingInteraction(null); setInteractionForm({ tipo: 'llamada', descripcion: '', resultado: '', fecha_interaccion: new Date().toISOString().slice(0, 16), proxima_accion: '', fecha_proxima_accion: null }); setFormErrors({}); setIsInteractionFormOpen(true) }} type="button">+ Registrar</button> : null}</div><div className="mt-5 space-y-3">{interactions.length === 0 ? <p className="rounded-xl bg-[var(--app-surface-muted)] p-4 text-sm text-[var(--app-text-muted)]">Todavía no hay interacciones registradas.</p> : interactions.map((interaction) => <article className="rounded-xl border border-[var(--app-border)] p-4" key={interaction.id}><div className="flex items-start justify-between gap-3"><div><p className="font-bold text-[var(--app-text)]">{getInteractionTypeLabel(interaction.tipo)}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{formatClientDate(interaction.fecha_interaccion)}</p></div><span className="text-xs text-[var(--app-text-muted)]">{interaction.registrado_por ? getFullClientName(interaction.registrado_por) : 'Sistema'}</span></div><p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-[var(--app-text)]">{interaction.descripcion}</p>{interaction.resultado ? <p className="mt-2 text-sm text-[var(--app-text-muted)]"><strong>Resultado:</strong> {interaction.resultado}</p> : null}{interaction.proxima_accion ? <p className="mt-2 text-sm text-[var(--app-text-muted)]"><strong>Próxima acción:</strong> {interaction.proxima_accion}{interaction.fecha_proxima_accion ? ' · ' + formatClientDate(interaction.fecha_proxima_accion) : ''}</p> : null}<div className="mt-4 flex flex-wrap gap-2">{can('interacciones.actualizar') ? <button className="app-button-secondary px-3 py-2 text-xs" onClick={() => editInteraction(interaction)} type="button">Editar</button> : null}{canDeleteInteractions ? <button className="app-button-secondary px-3 py-2 text-xs text-[var(--app-danger)]" onClick={() => void removeInteraction(interaction)} type="button">Eliminar</button> : null}</div></article>)}</div></section>
      <section className="app-card p-5 sm:p-6"><div><h2 className="text-lg font-bold text-[var(--app-text)]">Solicitudes de información</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">{requests.length} relacionadas</p></div><div className="mt-5 space-y-3">{requests.length === 0 ? <p className="rounded-xl bg-[var(--app-surface-muted)] p-4 text-sm text-[var(--app-text-muted)]">No hay solicitudes relacionadas.</p> : requests.map((request) => <article className="rounded-xl border border-[var(--app-border)] p-4" key={request.id}><div className="flex items-start justify-between gap-3"><div><p className="font-bold text-[var(--app-text)]">{request.inmueble?.titulo ?? 'Solicitud general'}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{formatClientDate(request.fecha_solicitud)} · {request.origen}</p></div><RequestStatus value={request.estado} /></div>{request.mensaje ? <p className="mt-3 whitespace-pre-wrap text-sm text-[var(--app-text-muted)]">{request.mensaje}</p> : null}<button className="app-button-secondary mt-4 px-3 py-2 text-xs" onClick={() => navigate('/solicitudes/' + request.id)} type="button">Ver solicitud</button></article>)}</div></section>
    </div>
    {isInterestFormOpen ? <div aria-modal="true" className="fixed inset-0 z-50 grid place-items-center bg-slate-950/55 p-4" role="dialog"><form className="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-6 shadow-2xl" onSubmit={(event) => void saveInterest(event)}><div className="flex items-start justify-between gap-4"><div><h2 className="text-xl font-bold text-[var(--app-text)]">{editingInterest ? 'Editar interés' : 'Agregar inmueble de interés'}</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">Registra el nivel y el seguimiento de preferencia.</p></div><button aria-label="Cerrar" className="app-icon-button" onClick={() => setIsInterestFormOpen(false)} type="button">×</button></div><div className="mt-5 space-y-4">{!editingInterest ? <label><span className="app-label">Inmueble</span><select className="app-select w-full" onChange={(event) => setInterestForm((current) => ({ ...current, inmueble_id: event.target.value ? Number(event.target.value) : undefined }))} required value={interestForm.inmueble_id ?? ''}><option value="">Selecciona un inmueble</option>{properties.filter((property) => !interests.some((interest) => interest.inmueble_id === property.id)).map((property) => <option key={property.id} value={property.id}>{property.codigo} · {property.titulo}</option>)}</select><FieldError errors={formErrors} field="inmueble_id" /></label> : null}<label><span className="app-label">Nivel de interés</span><select className="app-select w-full" onChange={(event) => setInterestForm((current) => ({ ...current, nivel_interes: (event.target.value || null) as NivelInteres | null }))} value={interestForm.nivel_interes ?? ''}><option value="">Sin definir</option>{interestLevels.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label><label><span className="app-label">Estado</span><select className="app-select w-full" onChange={(event) => setInterestForm((current) => ({ ...current, estado: event.target.value as EstadoInteres }))} value={interestForm.estado ?? 'activo'}>{interestStatuses.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label><label><span className="app-label">Notas</span><textarea className="app-input min-h-24 resize-y" onChange={(event) => setInterestForm((current) => ({ ...current, notas: event.target.value }))} value={interestForm.notas ?? ''} /></label></div><div className="mt-6 flex justify-end gap-3"><button className="app-button-secondary" onClick={() => setIsInterestFormOpen(false)} type="button">Cancelar</button><button className="app-button-primary" type="submit">Guardar interés</button></div></form></div> : null}
    {isInteractionFormOpen ? <div aria-modal="true" className="fixed inset-0 z-50 grid place-items-center bg-slate-950/55 p-4" role="dialog"><form className="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-6 shadow-2xl" onSubmit={(event) => void saveInteraction(event)}><div className="flex items-start justify-between gap-4"><div><h2 className="text-xl font-bold text-[var(--app-text)]">{editingInteraction ? 'Editar interacción' : 'Registrar interacción'}</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">Documenta el contacto y la próxima acción.</p></div><button aria-label="Cerrar" className="app-icon-button" onClick={() => setIsInteractionFormOpen(false)} type="button">×</button></div><div className="mt-5 space-y-4"><label><span className="app-label">Tipo</span><select className="app-select w-full" onChange={(event) => setInteractionForm((current) => ({ ...current, tipo: event.target.value as TipoInteraccion }))} value={interactionForm.tipo ?? ''}>{interactionTypes.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label><label><span className="app-label">Descripción</span><textarea className="app-input min-h-28 resize-y" onChange={(event) => setInteractionForm((current) => ({ ...current, descripcion: event.target.value }))} required value={interactionForm.descripcion ?? ''} /><FieldError errors={formErrors} field="descripcion" /></label><label><span className="app-label">Fecha de interacción</span><input className="app-input" onChange={(event) => setInteractionForm((current) => ({ ...current, fecha_interaccion: event.target.value }))} required type="datetime-local" value={interactionForm.fecha_interaccion ?? ''} /></label><label><span className="app-label">Resultado</span><input className="app-input" onChange={(event) => setInteractionForm((current) => ({ ...current, resultado: event.target.value }))} value={interactionForm.resultado ?? ''} /></label><label><span className="app-label">Próxima acción</span><input className="app-input" onChange={(event) => setInteractionForm((current) => ({ ...current, proxima_accion: event.target.value }))} value={interactionForm.proxima_accion ?? ''} /></label><label><span className="app-label">Fecha de próxima acción</span><input className="app-input" onChange={(event) => setInteractionForm((current) => ({ ...current, fecha_proxima_accion: event.target.value }))} type="datetime-local" value={interactionForm.fecha_proxima_accion ?? ''} /></label></div><div className="mt-6 flex justify-end gap-3"><button className="app-button-secondary" onClick={() => setIsInteractionFormOpen(false)} type="button">Cancelar</button><button className="app-button-primary" type="submit">Guardar interacción</button></div></form></div> : null}
  </div>
}
