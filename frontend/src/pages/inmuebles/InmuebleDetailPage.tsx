import { useEffect, useState } from 'react'
import { InmuebleStatusBadge, OperationBadge } from '../../components/inmuebles/InmuebleStatusBadge.tsx'
import { InmuebleGallery } from '../../components/inmuebles/InmuebleGallery.tsx'
import { PropertyPrice } from '../../components/inmuebles/PropertyPrice.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { ApiError } from '../../services/http.ts'
import { deleteInmueble, getInmueble } from '../../services/inmuebles.ts'
import { navigate } from '../../router/navigation.ts'
import type { InmuebleDetail } from '../../types/inmuebles.ts'
import { ForbiddenPage } from '../errors/ForbiddenPage.tsx'
import { NotFoundPage } from '../errors/NotFoundPage.tsx'

function DetailItem({ label, value }: { label: string; value: React.ReactNode }): React.JSX.Element {
  return <div><dt className="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--app-text-muted)]">{label}</dt><dd className="mt-1 text-sm font-semibold text-[var(--app-text)]">{value || '—'}</dd></div>
}

export function InmuebleDetailPage({ id }: { id: number }): React.JSX.Element {
  const { can } = useAuth()
  const [property, setProperty] = useState<InmuebleDetail | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [deleteError, setDeleteError] = useState<unknown>(null)
  const [isDeleting, setIsDeleting] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)
  const [reloadKey, setReloadKey] = useState(0)
  const [loadedRequestKey, setLoadedRequestKey] = useState<string | null>(null)
  const requestKey = `${id}-${reloadKey}`

  useEffect(() => {
    const controller = new AbortController()
    getInmueble(id, controller.signal).then((loaded) => {
      setProperty(loaded)
      setError(null)
      setLoadedRequestKey(requestKey)
    }).catch((nextError: unknown) => {
      if (!controller.signal.aborted) {
        setError(nextError)
        setLoadedRequestKey(requestKey)
      }
    })
    return () => controller.abort()
  }, [id, reloadKey, requestKey])

  const requestLoaded = loadedRequestKey === requestKey
  if (requestLoaded && error instanceof ApiError && error.status === 403) return <ForbiddenPage />
  if (requestLoaded && error instanceof ApiError && error.status === 404) return <NotFoundPage />
  if (requestLoaded && error) return <ErrorState message="No pudimos cargar el inmueble." onRetry={() => { setError(null); setLoadedRequestKey(null); setReloadKey((current) => current + 1) }} />
  if (!requestLoaded || !property) return <LoadingState label="Cargando inmueble..." />

  const location = [property.calle, property.numero_exterior, property.numero_interior, property.colonia, property.municipio, property.estado_ubicacion, property.codigo_postal].filter(Boolean).join(', ')
  const features = [
    ['Habitaciones', property.habitaciones],
    ['Baños completos', property.banos_completos],
    ['Medios baños', property.medios_banos],
    ['Estacionamientos', property.estacionamientos],
    ['Niveles', property.niveles],
    ['Terreno', property.superficie_terreno_m2 ? `${property.superficie_terreno_m2} m²` : '—'],
    ['Construcción', property.superficie_construccion_m2 ? `${property.superficie_construccion_m2} m²` : '—'],
  ] as const

  const handleDelete = async (): Promise<void> => {
    setIsDeleting(true)
    setDeleteError(null)
    try {
      await deleteInmueble(id)
      navigate('/inmuebles')
    } catch (nextError: unknown) {
      setDeleteError(nextError)
      setIsDeleting(false)
    }
  }

  return <div className="space-y-6">
    <header className="flex flex-col gap-5">
      <nav aria-label="Migas de pan" className="flex flex-wrap items-center gap-2 text-xs font-semibold text-[var(--app-text-muted)]"><button className="hover:text-[var(--app-accent)]" onClick={() => navigate('/inmuebles')} type="button">Inmuebles</button><span aria-hidden="true">›</span><span className="text-[var(--app-accent)]">{property.codigo}</span></nav>
      <div className="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div className="min-w-0"><button className="mb-4 text-sm font-semibold text-[var(--app-accent)] hover:underline" onClick={() => navigate('/inmuebles')} type="button">← Volver a inmuebles</button><div className="flex flex-wrap items-center gap-3"><h1 className="text-2xl font-bold tracking-[-0.035em] text-[var(--app-text)] sm:text-3xl">{property.titulo}</h1><InmuebleStatusBadge status={property.estado_disponibilidad} /></div><p className="mt-2 text-sm text-[var(--app-text-muted)]">{property.codigo} · {property.categoria?.nombre ?? 'Sin categoría'}</p></div>
        <div className="flex flex-wrap gap-2"><OperationBadge operation={property.tipo_operacion} />{can('inmuebles.actualizar') ? <button className="app-button-primary" onClick={() => navigate(`/inmuebles/${property.id}/editar`)} type="button">Editar inmueble</button> : null}{can('inmuebles.eliminar') ? <button className="app-button-secondary text-[var(--app-danger)]" onClick={() => { setDeleteError(null); setConfirmDelete(true) }} type="button">Eliminar</button> : null}</div>
      </div>
    </header>

    {deleteError && !confirmDelete ? <ErrorState message="No pudimos eliminar el inmueble." title="No se pudo eliminar" /> : null}
    <section className="app-card overflow-hidden"><div className="border-b border-[var(--app-border)] bg-[var(--app-surface-muted)] p-5 sm:p-6"><div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p className="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--app-text-muted)]">Precio efectivo</p><p className="mt-1 text-3xl font-bold text-[var(--app-text)]"><PropertyPrice property={property} /></p></div><p className="text-sm font-semibold text-[var(--app-text-muted)]">Publicado: <span className="text-[var(--app-text)]">{property.publicado ? 'Sí' : 'No'}</span></p></div></div>{property.descripcion ? <p className="whitespace-pre-line p-5 text-sm leading-7 text-[var(--app-text-muted)] sm:p-6">{property.descripcion}</p> : <p className="p-5 text-sm text-[var(--app-text-muted)] sm:p-6">Sin descripción registrada.</p>}</section>

    <InmuebleGallery inmuebleId={property.id} />

    <div className="grid gap-6 xl:grid-cols-2"><section className="app-card p-5 sm:p-6"><h2 className="text-lg font-bold text-[var(--app-text)]">Características</h2><dl className="mt-5 grid grid-cols-2 gap-x-5 gap-y-5 sm:grid-cols-3">{features.map(([label, value]) => <DetailItem key={label} label={label} value={value} />)}</dl></section><section className="app-card p-5 sm:p-6"><h2 className="text-lg font-bold text-[var(--app-text)]">Ubicación</h2><p className="mt-5 text-sm leading-7 text-[var(--app-text)]">{location || 'Sin ubicación registrada'}</p>{property.referencias ? <p className="mt-4 rounded-xl bg-[var(--app-surface-muted)] p-4 text-sm leading-6 text-[var(--app-text-muted)]">{property.referencias}</p> : null}</section></div>

    <section className="app-card p-5 sm:p-6"><h2 className="text-lg font-bold text-[var(--app-text)]">Información administrativa</h2><dl className="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"><DetailItem label="Categoría" value={property.categoria?.nombre} /><DetailItem label="Propietario" value={property.propietario?.nombre_razon_social} /><DetailItem label="Fecha de publicación" value={property.fecha_publicacion ? new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium' }).format(new Date(property.fecha_publicacion)) : '—'} /></dl></section>

    {confirmDelete ? <div aria-labelledby="confirm-delete-title" aria-modal="true" className="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" role="dialog"><div className="w-full max-w-md rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-6 shadow-2xl"><h2 className="text-xl font-bold text-[var(--app-text)]" id="confirm-delete-title">¿Eliminar este inmueble?</h2><p className="mt-3 text-sm leading-6 text-[var(--app-text-muted)]">El inmueble se retirará del listado. Esta acción conserva el historial mediante eliminación lógica.</p>{deleteError ? <p className="mt-4 rounded-lg bg-[var(--app-danger-surface)] p-3 text-sm font-semibold text-[var(--app-danger)]">No se pudo eliminar. Verifica tus permisos e intenta nuevamente.</p> : null}<div className="mt-6 flex justify-end gap-3"><button className="app-button-secondary" disabled={isDeleting} onClick={() => setConfirmDelete(false)} type="button">Cancelar</button><button className="rounded-xl bg-[var(--app-danger)] px-4 py-2.5 text-sm font-bold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60" disabled={isDeleting} onClick={() => void handleDelete()} type="button">{isDeleting ? 'Eliminando…' : 'Sí, eliminar'}</button></div></div></div> : null}
  </div>
}
