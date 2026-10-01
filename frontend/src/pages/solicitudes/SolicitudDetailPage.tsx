import { useEffect, useState } from 'react'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { SolicitudStatusBadge } from '../../components/solicitudes/SolicitudStatusBadge.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { navigate } from '../../router/navigation.ts'
import { listAgentesActivos } from '../../services/agentes.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'
import { convertSolicitudToCliente, deleteSolicitud, getSolicitud, updateSolicitud } from '../../services/solicitudes.ts'
import type { AgenteListItem } from '../../types/agentes.ts'
import type { EstadoSolicitud, MedioSolicitud, SolicitudInformacion } from '../../types/solicitudes.ts'
import { getSolicitudMediumLabel, getSolicitudStatusLabel } from '../../utils/solicitudes.ts'

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

function formatDate(value: string | null): string {
  if (!value) return '—'
  return new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}

function formatDateTimeLocal(value: string | null): string {
  if (!value) return ''
  const date = new Date(value)
  const pad = (part: number): string => String(part).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function toBackendDateTime(value: string): string | null {
  return value ? `${value.replace('T', ' ')}:00` : null
}

function fullName(value: { nombres: string; apellido_paterno: string; apellido_materno?: string | null } | null): string {
  if (!value) return 'Sin asignar'
  return [value.nombres, value.apellido_paterno, value.apellido_materno].filter(Boolean).join(' ')
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

function FieldError({ errors, field }: { errors: Record<string, string[]>; field: string }): React.JSX.Element | null {
  return errors[field]?.[0] ? <p className="mt-1 text-xs font-semibold text-[var(--app-danger)]">{errors[field][0]}</p> : null
}

function DetailItem({ label, value }: { label: string; value: string }): React.JSX.Element {
  return <div><dt className="text-xs font-bold uppercase tracking-[0.1em] text-[var(--app-text-muted)]">{label}</dt><dd className="mt-1 break-words text-sm font-semibold text-[var(--app-text)]">{value}</dd></div>
}

export function SolicitudDetailPage({ id }: { id: number }): React.JSX.Element {
  const { can, user } = useAuth()
  const [request, setRequest] = useState<SolicitudInformacion | null>(null)
  const [agents, setAgents] = useState<AgenteListItem[]>([])
  const [error, setError] = useState<unknown>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [isDeleting, setIsDeleting] = useState(false)
  const [isConverting, setIsConverting] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)
  const [notice, setNotice] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})
  const [status, setStatus] = useState<EstadoSolicitud>('nueva')
  const [medium, setMedium] = useState<MedioSolicitud | ''>('')
  const [assignee, setAssignee] = useState('')
  const [attentionDate, setAttentionDate] = useState('')
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [phone, setPhone] = useState('')
  const [message, setMessage] = useState('')

  const canUpdate = can('solicitudes.actualizar')
  const canDelete = can('solicitudes.eliminar')
  const canConvert = can('clientes.crear') && canUpdate
  const canAssign = Boolean(user?.roles.some((role) => role === 'Administrador' || role === 'Asistente')) && canUpdate
  const isAgent = user?.roles.includes('Agente Inmobiliario') ?? false

  function syncFormValues(nextRequest: SolicitudInformacion): void {
    setStatus(nextRequest.estado)
    setMedium(nextRequest.medio_preferido ?? '')
    setAssignee(nextRequest.atendida_por_user_id ? String(nextRequest.atendida_por_user_id) : '')
    setAttentionDate(formatDateTimeLocal(nextRequest.fecha_atencion))
    setName(nextRequest.nombre)
    setEmail(nextRequest.email)
    setPhone(nextRequest.telefono ?? '')
    setMessage(nextRequest.mensaje ?? '')
  }

  useEffect(() => {
    const controller = new AbortController()
    getSolicitud(id, controller.signal)
      .then((nextRequest) => { setRequest(nextRequest); syncFormValues(nextRequest) })
      .catch((nextError: unknown) => { if (!isAbortError(nextError)) setError(nextError) })
      .finally(() => { if (!controller.signal.aborted) setIsLoading(false) })
    return () => controller.abort()
  }, [id])

  useEffect(() => {
    if (!canAssign) return
    const controller = new AbortController()
    listAgentesActivos(controller.signal)
      .then((response) => setAgents(response.data.filter((agent) => agent.user.estado === 'activo')))
      .catch((nextError: unknown) => { if (!isAbortError(nextError)) setAgents([]) })
    return () => controller.abort()
  }, [canAssign])

  const isForbidden = error instanceof ApiError && error.status === 403
  const agentOptions = agents

  async function save(): Promise<void> {
    if (!request) return
    setIsSaving(true)
    setFieldErrors({})
    setNotice('')
    try {
      const nextDate = status === 'atendida' && !attentionDate ? new Date().toISOString().slice(0, 16) : attentionDate
      const updated = await updateSolicitud(id, {
        ...(canAssign || !isAgent ? { nombre: name, email, telefono: phone || null, mensaje: message || null, medio_preferido: medium || null } : {}),
        estado: status,
        ...(canAssign ? { atendida_por_user_id: assignee ? Number(assignee) : null } : {}),
        fecha_atencion: toBackendDateTime(nextDate),
      })
      setRequest(updated)
      setNotice('Solicitud actualizada correctamente.')
    } catch (nextError: unknown) {
      setFieldErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    } finally {
      setIsSaving(false)
    }
  }

  async function convertToClient(): Promise<void> {
    if (!request) return
    setIsConverting(true)
    setNotice('')
    try {
      const result = await convertSolicitudToCliente(id)
      const refreshed = await getSolicitud(id)
      setRequest(refreshed)
      syncFormValues(refreshed)
      setNotice(result.creado ? 'La solicitud se convirtió en un nuevo cliente prospecto.' : 'La solicitud se vinculó con el cliente existente del mismo correo.')
    } catch (nextError: unknown) {
      setFieldErrors(getValidationErrors(nextError instanceof ApiError ? nextError.payload : null))
    } finally {
      setIsConverting(false)
    }
  }

  async function remove(): Promise<void> {
    setIsDeleting(true)
    try {
      await deleteSolicitud(id)
      navigate('/solicitudes')
    } catch (nextError: unknown) {
      setError(nextError)
      setConfirmDelete(false)
    } finally {
      setIsDeleting(false)
    }
  }

  if (isLoading) return <LoadingState label="Cargando solicitud..." />
  if (isForbidden) return <ErrorState title="No tienes acceso a esta solicitud" message="La solicitud no está dentro del alcance de tu cuenta." onRetry={() => window.location.reload()} />
  if (error || !request) return <ErrorState title="No pudimos cargar la solicitud" onRetry={() => window.location.reload()} />

  return <div className="space-y-6">
    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><button className="text-sm font-bold text-[var(--app-accent)] hover:underline" onClick={() => navigate('/solicitudes')} type="button">← Volver a solicitudes</button><p className="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-[var(--app-accent)]">Solicitud #{request.id}</p><h1 className="mt-2 text-2xl font-bold tracking-[-0.035em] text-[var(--app-text)] sm:text-3xl">{request.nombre}</h1><p className="mt-2 text-sm text-[var(--app-text-muted)]">Recibida el {formatDate(request.fecha_solicitud)}</p></div><div className="flex flex-wrap gap-3"><SolicitudStatusBadge status={request.estado} />{canDelete ? <button className="app-button-secondary text-[var(--app-danger)]" onClick={() => setConfirmDelete(true)} type="button">Eliminar</button> : null}</div></div>
    {notice ? <div aria-live="polite" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200">{notice}</div> : null}
    {fieldErrors.form ? <div className="rounded-xl bg-[var(--app-danger-surface)] p-4 text-sm font-semibold text-[var(--app-danger)]">{fieldErrors.form[0]}</div> : null}
    <div className="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
      <div className="space-y-6">
        <section className="app-card p-5 sm:p-6"><div className="flex items-start justify-between gap-4"><div><h2 className="text-lg font-bold text-[var(--app-text)]">Datos de contacto</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">Información enviada por la persona interesada.</p></div>{request.cliente ? <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">Cliente vinculado</span> : null}</div>{canUpdate && !isAgent ? <div className="mt-5 grid gap-4 sm:grid-cols-2"><label><span className="app-label">Nombre</span><input className="app-input" onChange={(event) => setName(event.target.value)} value={name} /><FieldError errors={fieldErrors} field="nombre" /></label><label><span className="app-label">Correo</span><input className="app-input" onChange={(event) => setEmail(event.target.value)} type="email" value={email} /><FieldError errors={fieldErrors} field="email" /></label><label><span className="app-label">Teléfono</span><input className="app-input" onChange={(event) => setPhone(event.target.value)} value={phone} /><FieldError errors={fieldErrors} field="telefono" /></label><label><span className="app-label">Medio preferido</span><select className="app-select w-full" onChange={(event) => setMedium(event.target.value as MedioSolicitud | '')} value={medium}><option value="">No especificado</option>{mediums.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select><FieldError errors={fieldErrors} field="medio_preferido" /></label><label className="sm:col-span-2"><span className="app-label">Mensaje</span><textarea className="app-input min-h-28 resize-y" onChange={(event) => setMessage(event.target.value)} value={message} /><FieldError errors={fieldErrors} field="mensaje" /></label></div> : <dl className="mt-5 grid gap-5 sm:grid-cols-2"><DetailItem label="Correo" value={request.email} /><DetailItem label="Teléfono" value={request.telefono ?? 'No proporcionado'} /><DetailItem label="Medio preferido" value={getSolicitudMediumLabel(request.medio_preferido)} /><DetailItem label="Origen" value={request.origen} /><div className="sm:col-span-2"><dt className="text-xs font-bold uppercase tracking-[0.1em] text-[var(--app-text-muted)]">Mensaje</dt><dd className="mt-2 whitespace-pre-wrap text-sm leading-6 text-[var(--app-text)]">{request.mensaje || 'Sin mensaje adicional.'}</dd></div></dl>}</section>
        <section className="app-card p-5 sm:p-6"><h2 className="text-lg font-bold text-[var(--app-text)]">Inmueble consultado</h2>{request.inmueble ? <div className="mt-4 flex flex-col justify-between gap-4 rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-muted)] p-4 sm:flex-row sm:items-center"><div><p className="text-xs font-bold uppercase tracking-[0.1em] text-[var(--app-accent)]">{request.inmueble.codigo}</p><p className="mt-1 font-bold text-[var(--app-text)]">{request.inmueble.titulo}</p><p className="mt-1 text-sm text-[var(--app-text-muted)]">{request.inmueble.publicado ? 'Publicado en catálogo público' : 'No publicado actualmente'}</p></div><button className="app-button-secondary" onClick={() => navigate(`/inmuebles/${request.inmueble?.id}`)} type="button">Ver inmueble</button></div> : <p className="mt-4 rounded-xl bg-[var(--app-surface-muted)] p-4 text-sm text-[var(--app-text-muted)]">Esta solicitud no está asociada a un inmueble específico.</p>}</section>
        {request.cliente ? <section className="app-card p-5 sm:p-6"><h2 className="text-lg font-bold text-[var(--app-text)]">Cliente relacionado</h2><dl className="mt-5 grid gap-5 sm:grid-cols-2"><DetailItem label="Nombre" value={fullName(request.cliente)} /><DetailItem label="ID de cliente" value={String(request.cliente.id)} /></dl><p className="mt-4 text-sm text-[var(--app-text-muted)]">La ficha completa de clientes estará disponible en el siguiente módulo.</p></section> : null}
      </div>
      <aside className="space-y-6">
        <section className="app-card p-5 sm:p-6"><h2 className="text-lg font-bold text-[var(--app-text)]">Seguimiento</h2><div className="mt-5 space-y-4"><label><span className="app-label">Estado</span><select className="app-select w-full" disabled={!canUpdate} onChange={(event) => setStatus(event.target.value as EstadoSolicitud)} value={status}>{statuses.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select><FieldError errors={fieldErrors} field="estado" /></label>{canAssign ? <label><span className="app-label">Agente responsable</span><select className="app-select w-full" onChange={(event) => setAssignee(event.target.value)} value={assignee}><option value="">Sin asignar</option>{agentOptions.map((agent) => <option key={agent.user_id} value={agent.user_id}>{fullName(agent.user)} · {agent.numero_empleado}</option>)}</select><FieldError errors={fieldErrors} field="atendida_por_user_id" /></label> : <DetailItem label="Responsable" value={fullName(request.atendida_por)} />}{canUpdate ? <label><span className="app-label">Fecha de atención</span><input className="app-input" onChange={(event) => setAttentionDate(event.target.value)} type="datetime-local" value={attentionDate} /><FieldError errors={fieldErrors} field="fecha_atencion" /></label> : <DetailItem label="Fecha de atención" value={formatDate(request.fecha_atencion)} />}{canUpdate ? <button className="app-button-primary w-full" disabled={isSaving} onClick={() => void save()} type="button">{isSaving ? 'Guardando…' : 'Guardar cambios'}</button> : null}</div></section>
        {!request.cliente && canConvert ? <section className="app-card border-[var(--app-accent-soft)] p-5 sm:p-6"><p className="text-xs font-bold uppercase tracking-[0.12em] text-[var(--app-accent)]">Siguiente paso</p><h2 className="mt-2 text-lg font-bold text-[var(--app-text)]">Convertir en cliente</h2><p className="mt-2 text-sm leading-6 text-[var(--app-text-muted)]">Crea un cliente prospecto con sus datos y vincula esta solicitud. Si el correo ya existe, reutilizaremos el cliente.</p><button className="app-button-primary mt-5 w-full" disabled={isConverting} onClick={() => void convertToClient()} type="button">{isConverting ? 'Convirtiendo…' : 'Convertir en cliente'}</button></section> : null}
        <section className="app-card p-5 sm:p-6"><h2 className="text-lg font-bold text-[var(--app-text)]">Resumen</h2><dl className="mt-5 grid gap-5"><DetailItem label="Estado actual" value={getSolicitudStatusLabel(request.estado)} /><DetailItem label="Origen" value={request.origen} /><DetailItem label="Creada" value={formatDate(request.created_at)} /><DetailItem label="Última actualización" value={formatDate(request.updated_at)} /></dl></section>
      </aside>
    </div>
    {confirmDelete ? <div aria-labelledby="confirm-delete-title" aria-modal="true" className="fixed inset-0 z-50 grid place-items-center bg-slate-950/55 p-4" role="dialog"><div className="w-full max-w-md rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-6 shadow-2xl"><h2 className="text-xl font-bold text-[var(--app-text)]" id="confirm-delete-title">¿Eliminar esta solicitud?</h2><p className="mt-3 text-sm leading-6 text-[var(--app-text-muted)]">Se conservará como eliminación lógica y dejará de aparecer en el seguimiento.</p><div className="mt-6 flex flex-col-reverse justify-end gap-3 sm:flex-row"><button className="app-button-secondary" disabled={isDeleting} onClick={() => setConfirmDelete(false)} type="button">Cancelar</button><button className="rounded-xl bg-[var(--app-danger)] px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60" disabled={isDeleting} onClick={() => void remove()} type="button">{isDeleting ? 'Eliminando…' : 'Sí, eliminar'}</button></div></div></div> : null}
  </div>
}
