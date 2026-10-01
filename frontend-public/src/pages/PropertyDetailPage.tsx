import { useEffect, useState } from 'react'
import { PublicNavbar } from '../components/PublicNavbar.tsx'
import { ErrorState, LoadingState } from '../components/PublicStates.tsx'
import { formatPrice, PropertyImage } from '../components/PublicPropertyCard.tsx'
import { PublicApiError } from '../http.ts'
import { navigate } from '../navigation.ts'
import { getProperty, recordPropertyView, submitInquiry, type PublicInquiryPayload } from '../services.ts'
import type { PublicImage, PublicProperty } from '../types.ts'

function availabilityLabel(value: string): string {
  return { disponible: 'Disponible', vendido: 'Vendido', rentado: 'Rentado', inactivo: 'No disponible' }[value] ?? value
}

function Gallery({ property }: { property: PublicProperty }): React.JSX.Element {
  const images = property.imagenes ?? []
  const firstImage = images[0] ?? property.imagen_principal
  const [active, setActive] = useState<PublicImage | null>(firstImage)

  useEffect(() => setActive(firstImage), [firstImage])

  if (images.length === 0) return <div className="public-card overflow-hidden"><PropertyImage large property={property} /></div>

  return <div className="public-card overflow-hidden"><div className="bg-[var(--public-surface-muted)] p-2 sm:p-4">{active?.url_publica ? <img alt={active.texto_alternativo || `Imagen de ${property.titulo}`} className="h-72 w-full rounded-xl object-contain sm:h-[30rem]" src={active.url_publica} /> : <PropertyImage large property={property} />}</div><div className="grid grid-cols-4 gap-2 border-t border-[var(--public-border)] p-3 sm:grid-cols-6">{images.map((image, index) => <button aria-label={`Ver imagen ${index + 1} de ${images.length}`} aria-pressed={active?.url_publica === image.url_publica} className={`overflow-hidden rounded-lg border-2 ${active?.url_publica === image.url_publica ? 'border-[var(--public-accent)]' : 'border-transparent'}`} key={`${image.url_publica}-${index}`} onClick={() => setActive(image)} type="button">{image.url_publica ? <img alt="" className="aspect-[4/3] w-full object-cover" loading="lazy" src={image.url_publica} /> : <span className="grid aspect-[4/3] place-items-center bg-[var(--public-surface-muted)] text-xs">Sin imagen</span>}</button>)}</div></div>
}

function InquiryForm({ property }: { property: PublicProperty }): React.JSX.Element {
  const [values, setValues] = useState<PublicInquiryPayload>({ nombre: '', email: '', telefono: '', mensaje: '', medio_preferido: 'correo', inmueble_id: property.id })
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle')
  const update = (field: Exclude<keyof PublicInquiryPayload, 'inmueble_id'>, value: string): void => {
    setValues((current) => ({ ...current, [field]: value }))
    if (status === 'error') setStatus('idle')
  }

  const submit = async (event: React.FormEvent<HTMLFormElement>): Promise<void> => {
    event.preventDefault()
    setStatus('sending')
    try {
      await submitInquiry(values)
      setStatus('success')
    } catch {
      setStatus('error')
    }
  }

  if (status === 'success') return <section className="public-card p-6 sm:p-7" id="solicitud" aria-labelledby="request-success-heading"><p className="public-kicker text-[var(--public-accent)]">Solicitud enviada</p><h2 className="public-display mt-2 text-3xl font-bold text-[var(--public-text)]" id="request-success-heading">Ya tenemos tus datos.</h2><p className="mt-3 text-sm leading-6 text-[var(--public-muted)]">Recibimos tu solicitud sobre <strong className="text-[var(--public-text)]">{property.titulo}</strong>. El equipo dará seguimiento por el medio que elegiste.</p><button className="public-button-secondary mt-6 w-full" onClick={() => setStatus('idle')} type="button">Enviar otra solicitud</button></section>

  return <section className="public-card p-6 sm:p-7" id="solicitud" aria-labelledby="request-heading"><p className="public-kicker text-[var(--public-accent)]">Atención personalizada</p><h2 className="public-display mt-2 text-3xl font-bold text-[var(--public-text)]" id="request-heading">Solicitar información</h2><p className="mt-2 text-sm leading-6 text-[var(--public-muted)]">Cuéntanos cómo contactarte para resolver tus dudas o agendar una visita de esta propiedad.</p><form className="mt-5 space-y-4" onSubmit={(event) => void submit(event)}><label className="public-label" htmlFor="inquiry-name">Nombre<input autoComplete="name" className="public-input mt-2" id="inquiry-name" minLength={2} onChange={(event) => update('nombre', event.target.value)} required value={values.nombre} /></label><label className="public-label" htmlFor="inquiry-email">Correo electrónico<input autoComplete="email" className="public-input mt-2" id="inquiry-email" onChange={(event) => update('email', event.target.value)} required type="email" value={values.email} /></label><label className="public-label" htmlFor="inquiry-phone">Teléfono <span className="font-normal text-[var(--public-muted)]">(opcional)</span><input autoComplete="tel" className="public-input mt-2" id="inquiry-phone" onChange={(event) => update('telefono', event.target.value)} value={values.telefono} /></label><label className="public-label" htmlFor="inquiry-message">Mensaje <span className="font-normal text-[var(--public-muted)]">(opcional)</span><textarea className="public-input mt-2 min-h-28 resize-y" id="inquiry-message" onChange={(event) => update('mensaje', event.target.value)} placeholder="¿Qué te gustaría saber?" value={values.mensaje} /></label><label className="public-label" htmlFor="inquiry-channel">Prefiero contacto por<select className="public-input mt-2" id="inquiry-channel" onChange={(event) => update('medio_preferido', event.target.value)} value={values.medio_preferido}><option value="correo">Correo electrónico</option><option value="telefono">Teléfono</option><option value="whatsapp">WhatsApp</option></select></label>{status === 'error' ? <p aria-live="polite" className="text-sm font-semibold text-[var(--public-danger)]">No pudimos enviar la solicitud. Tus datos siguen aquí; revisa la información e inténtalo nuevamente.</p> : null}<p className="text-xs leading-5 text-[var(--public-muted)]">Al enviar aceptas que usemos estos datos para atender tu solicitud. Consulta el <a className="underline decoration-[var(--public-accent)] underline-offset-2" href="/aviso-de-privacidad">aviso de privacidad</a>.</p><button className="public-button-primary w-full" disabled={status === 'sending'} type="submit">{status === 'sending' ? 'Enviando solicitud...' : 'Solicitar información'}</button></form></section>
}

function DetailItem({ label, value }: { label: string; value: string | number | null }): React.JSX.Element { return <div><dt className="text-sm text-[var(--public-muted)]">{label}</dt><dd className="mt-1 font-bold text-[var(--public-text)]">{value ?? '—'}</dd></div> }

export function PropertyDetailPage({ slug, theme, onToggleTheme }: { slug: string; theme: 'light' | 'dark'; onToggleTheme: () => void }): React.JSX.Element {
  const [property, setProperty] = useState<PublicProperty | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const controller = new AbortController()
    setLoading(true)
    setError(null)
    setProperty(null)
    getProperty(slug, controller.signal).then((item) => { setProperty(item); void recordPropertyView(item.id).catch(() => undefined) }).catch((nextError: unknown) => { if (!(nextError instanceof DOMException && nextError.name === 'AbortError')) setError(nextError) }).finally(() => { if (!controller.signal.aborted) setLoading(false) })
    return () => controller.abort()
  }, [slug])

  return <div className="public-page"><PublicNavbar onToggleTheme={onToggleTheme} theme={theme} /><main className="public-container py-8 sm:py-12">{loading ? <LoadingState /> : null}{error ? <ErrorState detail={error instanceof PublicApiError && error.status === 404 ? 'Esta propiedad ya no está disponible públicamente.' : undefined} onRetry={() => window.location.reload()} /> : null}{property ? <><button className="public-back-link" onClick={() => navigate('/propiedades')} type="button">← Volver al catálogo</button><div className="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]"><div><div className="flex flex-wrap items-start justify-between gap-4"><div><p className="public-kicker">{property.codigo} · {property.categoria?.nombre ?? 'Propiedad'}</p><h1 className="public-display mt-2 text-4xl font-bold text-[var(--public-text)] sm:text-5xl">{property.titulo}</h1><p className="mt-3 text-[var(--public-muted)]">{[property.municipio, property.estado_ubicacion].filter(Boolean).join(', ') || 'Ubicación por confirmar'}</p></div><div className="flex items-center gap-2"><span className="public-operation-badge">{property.tipo_operacion === 'venta' ? 'Venta' : 'Renta'}</span><span className="public-status-badge">{availabilityLabel(property.estado_disponibilidad)}</span></div></div><div className="mt-7"><Gallery property={property} /></div><section className="public-card mt-7 p-6 sm:p-8"><div className="flex flex-wrap items-end justify-between gap-5"><div><p className="public-kicker text-[var(--public-accent)]">{property.tipo_operacion === 'venta' ? 'Precio de venta' : 'Renta mensual'}</p><p className="mt-2 text-4xl font-black tracking-[-0.05em] text-[var(--public-text)]">{formatPrice(property)}</p></div><a className="public-button-primary" href="#solicitud">Solicitar información</a></div><p className="mt-6 whitespace-pre-line text-base leading-8 text-[var(--public-muted)]">{property.descripcion || 'Solicita más información para conocer todos los detalles de esta propiedad.'}</p></section><section className="public-card mt-7 p-6 sm:p-8" aria-labelledby="features-heading"><h2 className="public-display text-3xl font-bold text-[var(--public-text)]" id="features-heading">Características</h2><dl className="mt-6 grid grid-cols-2 gap-x-5 gap-y-6 sm:grid-cols-3"><DetailItem label="Habitaciones" value={property.habitaciones} /><DetailItem label="Baños" value={property.banos_completos} /><DetailItem label="Medios baños" value={property.medios_banos} /><DetailItem label="Estacionamientos" value={property.estacionamientos} /><DetailItem label="Construcción" value={property.superficie_construccion_m2 ? `${property.superficie_construccion_m2} m²` : null} /><DetailItem label="Terreno" value={property.superficie_terreno_m2 ? `${property.superficie_terreno_m2} m²` : null} /><DetailItem label="Niveles" value={property.niveles} /></dl></section></div><aside><div className="lg:sticky lg:top-6"><InquiryForm property={property} /></div></aside></div></> : null}</main></div>
}
