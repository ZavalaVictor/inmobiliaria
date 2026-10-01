import type { LaravelPaginatedResponse } from './pagination.ts'
import type { InmuebleImagen } from './inmueble-imagenes.ts'

export type TipoOperacion = 'venta' | 'renta'
export type EstadoDisponibilidadInmueble = 'disponible' | 'vendido' | 'rentado' | 'inactivo'
export type PublishedFilter = 'true' | 'false'
export type InmuebleSort = 'id' | 'codigo' | 'titulo' | 'tipo_operacion' | 'estado_disponibilidad' | 'created_at' | 'updated_at'
export type SortDirection = 'asc' | 'desc'

export interface CategoriaResumen {
  id: number
  nombre: string
}

export interface PropietarioResumen {
  id: number
  nombre_razon_social: string
}

export interface InmuebleListItem {
  id: number
  codigo: string
  titulo: string
  slug: string
  descripcion: string | null
  tipo_operacion: TipoOperacion
  precio_venta: string | number | null
  renta_mensual: string | number | null
  superficie_terreno_m2: string | number | null
  superficie_construccion_m2: string | number | null
  habitaciones: number | null
  banos_completos: number | null
  medios_banos: number | null
  estacionamientos: number | null
  niveles: number | null
  calle: string | null
  numero_exterior: string | null
  numero_interior: string | null
  colonia: string | null
  municipio: string | null
  estado_ubicacion: string | null
  codigo_postal: string | null
  referencias: string | null
  latitud: string | number | null
  longitud: string | number | null
  estado_disponibilidad: EstadoDisponibilidadInmueble
  publicado: boolean
  fecha_publicacion: string | null
  created_at: string | null
  updated_at: string | null
  categoria: CategoriaResumen | null
  imagen_principal?: InmuebleImagen | null
  propietario?: PropietarioResumen | null
  propietario_id?: number | null
}

export interface InmuebleListParams {
  q?: string
  tipo_operacion?: TipoOperacion
  estado_disponibilidad?: EstadoDisponibilidadInmueble
  categoria_id?: number
  propietario_id?: number
  habitaciones?: number
  banos_completos?: number
  publicado?: boolean
  municipio?: string
  estado_ubicacion?: string
  page?: number
  per_page?: number
  sort?: InmuebleSort
  direction?: SortDirection
}

export interface InmuebleFilters {
  q: string
  tipo_operacion: TipoOperacion | ''
  estado_disponibilidad: EstadoDisponibilidadInmueble | ''
  categoria_id: string
  propietario_id: string
  habitaciones: string
  banos_completos: string
  publicado: PublishedFilter | ''
  municipio: string
  estado_ubicacion: string
  page: number
  per_page: 15 | 25 | 50
  sort: InmuebleSort
  direction: SortDirection
}

export interface InmuebleFormValues {
  propietario_id: string
  categoria_id: string
  codigo: string
  titulo: string
  slug: string
  descripcion: string
  tipo_operacion: TipoOperacion
  precio_venta: string
  renta_mensual: string
  superficie_terreno_m2: string
  superficie_construccion_m2: string
  habitaciones: string
  banos_completos: string
  medios_banos: string
  estacionamientos: string
  niveles: string
  calle: string
  numero_exterior: string
  numero_interior: string
  colonia: string
  municipio: string
  estado_ubicacion: string
  codigo_postal: string
  referencias: string
  latitud: string
  longitud: string
  estado_disponibilidad: EstadoDisponibilidadInmueble
  publicado: boolean
  fecha_publicacion: string
}

export interface InmuebleWritePayload {
  propietario_id?: number
  categoria_id?: number
  codigo?: string
  titulo?: string
  slug?: string
  descripcion?: string | null
  tipo_operacion?: TipoOperacion
  precio_venta?: string | null
  renta_mensual?: string | null
  superficie_terreno_m2?: string | null
  superficie_construccion_m2?: string | null
  habitaciones?: number | null
  banos_completos?: number | null
  medios_banos?: number | null
  estacionamientos?: number | null
  niveles?: number | null
  calle?: string
  numero_exterior?: string | null
  numero_interior?: string | null
  colonia?: string
  municipio?: string
  estado_ubicacion?: string
  codigo_postal?: string
  referencias?: string | null
  latitud?: string | null
  longitud?: string | null
  estado_disponibilidad?: EstadoDisponibilidadInmueble
  publicado?: boolean
  fecha_publicacion?: string | null
}

export type InmuebleDetail = InmuebleListItem
export type InmueblesResponse = LaravelPaginatedResponse<InmuebleListItem>
