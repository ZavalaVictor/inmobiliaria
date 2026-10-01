import type { LaravelPaginatedResponse } from './types-pagination.ts'

export type PublicTipoOperacion = 'venta' | 'renta'

export interface PublicImage {
  url_publica: string | null
  texto_alternativo: string | null
  es_principal?: boolean
  orden?: number
}

export interface PublicProperty {
  id: number
  codigo: string
  titulo: string
  slug: string
  descripcion: string | null
  tipo_operacion: PublicTipoOperacion
  precio: number | null
  moneda: 'MXN'
  municipio: string | null
  estado_ubicacion: string | null
  coordenadas: { latitud: number; longitud: number } | null
  habitaciones: number | null
  banos_completos: number | null
  medios_banos: number | null
  estacionamientos: number | null
  niveles: number | null
  superficie_terreno_m2: string | number | null
  superficie_construccion_m2: string | number | null
  estado_disponibilidad: string
  categoria: { id: number; nombre: string } | null
  imagen_principal: PublicImage | null
  imagenes?: PublicImage[]
}

export interface PublicFilters {
  q: string
  ubicacion: string
  tipo_inmueble: string
  tipo_operacion: PublicTipoOperacion | ''
  precio_min: string
  precio_max: string
  page: number
  per_page: 12 | 24 | 50
  sort: 'created_at' | 'titulo'
  direction: 'asc' | 'desc'
}

export type PublicPropertiesResponse = LaravelPaginatedResponse<PublicProperty>
