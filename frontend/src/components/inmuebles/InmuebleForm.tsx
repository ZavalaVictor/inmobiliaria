import { useState } from 'react'
import { PropertyLocationMap } from './PropertyLocationMap.tsx'
import type { CategoriaOption } from '../../types/categorias.ts'
import type { InmuebleFormValues, InmuebleWritePayload } from '../../types/inmuebles.ts'
import type { PropietarioOption } from '../../types/propietarios.ts'

interface InmuebleFormProps {
  initialValues: InmuebleFormValues
  categories: CategoriaOption[] | null
  owners: PropietarioOption[] | null
  canChangeRelations: boolean
  isSubmitting: boolean
  fieldErrors: Record<string, string[]>
  onSubmit: (payload: InmuebleWritePayload) => void
  onCancel: () => void
}

function toSlug(value: string): string {
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
}

function nullableText(value: string): string | null {
  return value.trim() === '' ? null : value.trim()
}

function optionalNumber(value: string): number | undefined {
  return value.trim() === '' ? undefined : Number(value)
}

function apiDate(value: string): string | null {
  if (!value) return null
  return value.length === 16 ? `${value.replace('T', ' ')}:00` : value.replace('T', ' ')
}

function toPayload(values: InmuebleFormValues, canChangeRelations: boolean): InmuebleWritePayload {
  const payload: InmuebleWritePayload = {
    codigo: values.codigo.trim(),
    titulo: values.titulo.trim(),
    slug: values.slug.trim(),
    descripcion: nullableText(values.descripcion),
    tipo_operacion: values.tipo_operacion,
    precio_venta: values.tipo_operacion === 'venta' ? nullableText(values.precio_venta) : null,
    renta_mensual: values.tipo_operacion === 'renta' ? nullableText(values.renta_mensual) : null,
    superficie_terreno_m2: nullableText(values.superficie_terreno_m2),
    superficie_construccion_m2: nullableText(values.superficie_construccion_m2),
    habitaciones: optionalNumber(values.habitaciones),
    banos_completos: optionalNumber(values.banos_completos),
    medios_banos: optionalNumber(values.medios_banos),
    estacionamientos: optionalNumber(values.estacionamientos),
    niveles: optionalNumber(values.niveles),
    calle: values.calle.trim(),
    numero_exterior: nullableText(values.numero_exterior),
    numero_interior: nullableText(values.numero_interior),
    colonia: values.colonia.trim(),
    municipio: values.municipio.trim(),
    estado_ubicacion: values.estado_ubicacion.trim(),
    codigo_postal: values.codigo_postal.trim(),
    referencias: nullableText(values.referencias),
    latitud: nullableText(values.latitud),
    longitud: nullableText(values.longitud),
    estado_disponibilidad: values.estado_disponibilidad,
    publicado: values.publicado,
    fecha_publicacion: apiDate(values.fecha_publicacion),
  }

  if (canChangeRelations) {
    if (values.propietario_id) payload.propietario_id = Number(values.propietario_id)
    if (values.categoria_id) payload.categoria_id = Number(values.categoria_id)
  }

  return payload
}

function Field({ children, error, label, required = false }: { children: React.ReactNode; error?: string[]; label: string; required?: boolean }): React.JSX.Element {
  return <label className="flex min-w-0 flex-col gap-1.5 text-sm font-semibold text-[var(--app-text)]"><span>{label}{required ? <span aria-hidden="true" className="ml-1 text-[var(--app-danger)]">*</span> : null}</span>{children}{error?.length ? <span className="text-xs font-medium text-[var(--app-danger)]">{error[0]}</span> : null}</label>
}

function inputClass(hasError: boolean): string {
  return `app-input ${hasError ? 'border-[var(--app-danger)]' : ''}`
}

function validate(values: InmuebleFormValues, canChangeRelations: boolean): Record<string, string[]> {
  const errors: Record<string, string[]> = {}
  const requiredFields: Array<[keyof InmuebleFormValues, string]> = [
    ['codigo', 'El código es obligatorio.'],
    ['titulo', 'El título es obligatorio.'],
    ['slug', 'El slug es obligatorio.'],
    ['calle', 'La calle es obligatoria.'],
    ['colonia', 'La colonia es obligatoria.'],
    ['municipio', 'El municipio es obligatorio.'],
    ['estado_ubicacion', 'El estado es obligatorio.'],
    ['codigo_postal', 'El código postal es obligatorio.'],
  ]

  if (canChangeRelations) {
    requiredFields.unshift(
      ['propietario_id', 'Selecciona un propietario.'],
      ['categoria_id', 'Selecciona una categoría.'],
    )
  }

  requiredFields.forEach(([field, message]) => {
    if (typeof values[field] === 'string' && values[field].trim() === '') errors[field] = [message]
  })

  const priceField = values.tipo_operacion === 'venta' ? 'precio_venta' : 'renta_mensual'
  if (values[priceField].trim() === '') {
    errors[priceField] = [values.tipo_operacion === 'venta' ? 'El precio de venta es obligatorio.' : 'La renta mensual es obligatoria.']
  }

  return errors
}

export function InmuebleForm({ initialValues, categories, owners, canChangeRelations, isSubmitting, fieldErrors, onSubmit, onCancel }: InmuebleFormProps): React.JSX.Element {
  const [values, setValues] = useState<InmuebleFormValues>(initialValues)
  const [slugTouched, setSlugTouched] = useState(Boolean(initialValues.slug))
  const [localErrors, setLocalErrors] = useState<Record<string, string[]>>({})
  const errorFor = (field: string): string[] | undefined => localErrors[field] ?? fieldErrors[field]
  const update = <K extends keyof InmuebleFormValues>(field: K, value: InmuebleFormValues[K]): void => {
    setValues((current) => ({ ...current, [field]: value }))
    setLocalErrors((current) => {
      if (!current[field]) return current
      const next = { ...current }
      delete next[field]
      return next
    })
  }

  return <form className="space-y-6" onSubmit={(event) => {
    event.preventDefault()
    const validationErrors = validate(values, canChangeRelations)
    setLocalErrors(validationErrors)
    if (Object.keys(validationErrors).length > 0) return
    onSubmit(toPayload(values, canChangeRelations))
  }}>
    <section className="app-card p-5 sm:p-6"><div className="mb-5"><h2 className="text-lg font-bold text-[var(--app-text)]">Datos generales</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">Identifica la propiedad y define su operación.</p></div><div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Field error={errorFor('propietario_id')} label="Propietario" required><select className="app-select w-full" disabled={!canChangeRelations || owners === null} onChange={(event) => update('propietario_id', event.target.value)} value={values.propietario_id}><option value="">{owners === null ? 'Catálogo no disponible' : 'Selecciona un propietario'}</option>{owners?.map((owner) => <option key={owner.id} value={owner.id}>{owner.nombre_razon_social}</option>)}</select></Field>
      <Field error={errorFor('categoria_id')} label="Categoría" required><select className="app-select w-full" disabled={!canChangeRelations || categories === null} onChange={(event) => update('categoria_id', event.target.value)} value={values.categoria_id}><option value="">{categories === null ? 'Catálogo no disponible' : 'Selecciona una categoría'}</option>{categories?.map((category) => <option key={category.id} value={category.id}>{category.nombre}</option>)}</select></Field>
      <Field error={errorFor('tipo_operacion')} label="Tipo de operación" required><select className="app-select w-full" onChange={(event) => update('tipo_operacion', event.target.value as InmuebleFormValues['tipo_operacion'])} value={values.tipo_operacion}><option value="venta">Venta</option><option value="renta">Renta</option></select></Field>
      <Field error={errorFor('codigo')} label="Código" required><input className={inputClass(Boolean(errorFor('codigo')))} onChange={(event) => update('codigo', event.target.value)} value={values.codigo} /></Field>
      <Field error={errorFor('titulo')} label="Título" required><input className={inputClass(Boolean(errorFor('titulo')))} onChange={(event) => { const title = event.target.value; update('titulo', title); if (!slugTouched) update('slug', toSlug(title)) }} value={values.titulo} /></Field>
      <Field error={errorFor('slug')} label="Slug" required><input className={inputClass(Boolean(errorFor('slug')))} onChange={(event) => { setSlugTouched(true); update('slug', event.target.value) }} value={values.slug} /></Field>
      <Field error={errorFor('precio_venta')} label="Precio de venta" required={values.tipo_operacion === 'venta'}><input className={inputClass(Boolean(errorFor('precio_venta')))} disabled={values.tipo_operacion !== 'venta'} min="0" onChange={(event) => update('precio_venta', event.target.value)} step="0.01" type="number" value={values.precio_venta} /></Field>
      <Field error={errorFor('renta_mensual')} label="Renta mensual" required={values.tipo_operacion === 'renta'}><input className={inputClass(Boolean(errorFor('renta_mensual')))} disabled={values.tipo_operacion !== 'renta'} min="0" onChange={(event) => update('renta_mensual', event.target.value)} step="0.01" type="number" value={values.renta_mensual} /></Field>
      <Field error={errorFor('estado_disponibilidad')} label="Disponibilidad"><select className="app-select w-full" onChange={(event) => update('estado_disponibilidad', event.target.value as InmuebleFormValues['estado_disponibilidad'])} value={values.estado_disponibilidad}><option value="disponible">Disponible</option><option value="vendido">Vendido</option><option value="rentado">Rentado</option><option value="inactivo">Inactivo</option></select></Field>
      <label className="flex items-center gap-3 self-end pb-2 text-sm font-semibold text-[var(--app-text)]"><input checked={values.publicado} onChange={(event) => update('publicado', event.target.checked)} type="checkbox" /> Publicar inmueble</label>
      <Field error={errorFor('fecha_publicacion')} label="Fecha de publicación"><input className={inputClass(Boolean(errorFor('fecha_publicacion')))} onChange={(event) => update('fecha_publicacion', event.target.value)} type="datetime-local" value={values.fecha_publicacion} /></Field>
    </div><Field error={errorFor('descripcion')} label="Descripción"><textarea className={`${inputClass(Boolean(errorFor('descripcion')))} min-h-28 resize-y`} onChange={(event) => update('descripcion', event.target.value)} value={values.descripcion} /></Field></section>

    <section className="app-card p-5 sm:p-6"><div className="mb-5"><h2 className="text-lg font-bold text-[var(--app-text)]">Características</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">Registra las dimensiones y espacios principales.</p></div><div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      {([['superficie_terreno_m2', 'Superficie terreno (m²)'], ['superficie_construccion_m2', 'Superficie construcción (m²)'], ['habitaciones', 'Habitaciones'], ['banos_completos', 'Baños completos'], ['medios_banos', 'Medios baños'], ['estacionamientos', 'Estacionamientos'], ['niveles', 'Niveles']] as const).map(([field, label]) => <Field error={errorFor(field)} key={field} label={label}><input className={inputClass(Boolean(errorFor(field)))} min="0" onChange={(event) => update(field, event.target.value)} step={field.includes('superficie') ? '0.01' : '1'} type="number" value={values[field]} /></Field>)}
    </div><div className="mt-6 border-t border-[var(--app-border)] pt-6"><div className="mb-4"><h3 className="text-base font-bold text-[var(--app-text)]">Ubicación en mapa</h3><p className="mt-1 text-sm text-[var(--app-text-muted)]">Define el punto que se mostrará en el mapa público.</p></div><PropertyLocationMap latitud={values.latitud} longitud={values.longitud} onChange={(latitud, longitud) => { update('latitud', latitud); update('longitud', longitud) }} /></div></section>

    <section className="app-card p-5 sm:p-6"><div className="mb-5"><h2 className="text-lg font-bold text-[var(--app-text)]">Ubicación</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">La dirección se utiliza para localizar el inmueble.</p></div><div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Field error={errorFor('calle')} label="Calle" required><input className={inputClass(Boolean(errorFor('calle')))} onChange={(event) => update('calle', event.target.value)} value={values.calle} /></Field><Field error={errorFor('numero_exterior')} label="Número exterior"><input className={inputClass(Boolean(errorFor('numero_exterior')))} onChange={(event) => update('numero_exterior', event.target.value)} value={values.numero_exterior} /></Field><Field error={errorFor('numero_interior')} label="Número interior"><input className={inputClass(Boolean(errorFor('numero_interior')))} onChange={(event) => update('numero_interior', event.target.value)} value={values.numero_interior} /></Field><Field error={errorFor('colonia')} label="Colonia" required><input className={inputClass(Boolean(errorFor('colonia')))} onChange={(event) => update('colonia', event.target.value)} value={values.colonia} /></Field><Field error={errorFor('municipio')} label="Municipio" required><input className={inputClass(Boolean(errorFor('municipio')))} onChange={(event) => update('municipio', event.target.value)} value={values.municipio} /></Field><Field error={errorFor('estado_ubicacion')} label="Estado" required><input className={inputClass(Boolean(errorFor('estado_ubicacion')))} onChange={(event) => update('estado_ubicacion', event.target.value)} value={values.estado_ubicacion} /></Field><Field error={errorFor('codigo_postal')} label="Código postal" required><input className={inputClass(Boolean(errorFor('codigo_postal')))} onChange={(event) => update('codigo_postal', event.target.value)} value={values.codigo_postal} /></Field><Field error={errorFor('referencias')} label="Referencias"><textarea className={`${inputClass(Boolean(errorFor('referencias')))} min-h-20 resize-y`} onChange={(event) => update('referencias', event.target.value)} value={values.referencias} /></Field><Field error={errorFor('latitud')} label="Latitud"><input className={inputClass(Boolean(errorFor('latitud')))} max="90" min="-90" onChange={(event) => update('latitud', event.target.value)} step="0.0000001" type="number" value={values.latitud} /></Field><Field error={errorFor('longitud')} label="Longitud"><input className={inputClass(Boolean(errorFor('longitud')))} max="180" min="-180" onChange={(event) => update('longitud', event.target.value)} step="0.0000001" type="number" value={values.longitud} /></Field>
    </div></section>
    <div className="flex flex-wrap justify-end gap-3"><button className="app-button-secondary" onClick={onCancel} type="button">Cancelar</button><button className="app-button-primary" disabled={isSubmitting} type="submit">{isSubmitting ? 'Guardando…' : 'Guardar inmueble'}</button></div>
  </form>
}
