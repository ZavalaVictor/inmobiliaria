import type { InmuebleFormValues, InmuebleListItem } from '../types/inmuebles.ts'

export const emptyInmuebleForm: InmuebleFormValues = {
  propietario_id: '',
  categoria_id: '',
  codigo: '',
  titulo: '',
  slug: '',
  descripcion: '',
  tipo_operacion: 'venta',
  precio_venta: '',
  renta_mensual: '',
  superficie_terreno_m2: '',
  superficie_construccion_m2: '',
  habitaciones: '',
  banos_completos: '',
  medios_banos: '',
  estacionamientos: '',
  niveles: '',
  calle: '',
  numero_exterior: '',
  numero_interior: '',
  colonia: '',
  municipio: '',
  estado_ubicacion: '',
  codigo_postal: '',
  referencias: '',
  latitud: '',
  longitud: '',
  estado_disponibilidad: 'disponible',
  publicado: false,
  fecha_publicacion: '',
}

function value(value: string | number | null | undefined): string {
  return value === null || value === undefined ? '' : String(value)
}

function localDateTime(valueToFormat: string | null): string {
  if (!valueToFormat) return ''
  const date = new Date(valueToFormat)
  if (Number.isNaN(date.getTime())) return ''
  const pad = (part: number): string => String(part).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

export function inmuebleToFormValues(property: InmuebleListItem): InmuebleFormValues {
  return {
    propietario_id: value(property.propietario_id ?? property.propietario?.id),
    categoria_id: value(property.categoria?.id),
    codigo: property.codigo,
    titulo: property.titulo,
    slug: property.slug,
    descripcion: property.descripcion ?? '',
    tipo_operacion: property.tipo_operacion,
    precio_venta: value(property.precio_venta),
    renta_mensual: value(property.renta_mensual),
    superficie_terreno_m2: value(property.superficie_terreno_m2),
    superficie_construccion_m2: value(property.superficie_construccion_m2),
    habitaciones: value(property.habitaciones),
    banos_completos: value(property.banos_completos),
    medios_banos: value(property.medios_banos),
    estacionamientos: value(property.estacionamientos),
    niveles: value(property.niveles),
    calle: property.calle ?? '',
    numero_exterior: property.numero_exterior ?? '',
    numero_interior: property.numero_interior ?? '',
    colonia: property.colonia ?? '',
    municipio: property.municipio ?? '',
    estado_ubicacion: property.estado_ubicacion ?? '',
    codigo_postal: property.codigo_postal ?? '',
    referencias: property.referencias ?? '',
    latitud: value(property.latitud),
    longitud: value(property.longitud),
    estado_disponibilidad: property.estado_disponibilidad,
    publicado: property.publicado,
    fecha_publicacion: localDateTime(property.fecha_publicacion),
  }
}
