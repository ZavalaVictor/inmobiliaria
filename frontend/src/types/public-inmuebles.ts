import type { LaravelPaginatedResponse } from './pagination.ts'

export type PublicTipoOperacion = 'venta' | 'renta'

export interface PublicInmuebleImagen {
  url_publica: string | null
  texto_alternativo: string | null
}

export interface PublicInmuebleListItem {
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
  superficie_terreno_m2: string | number | null
  superficie_construccion_m2: string | number | null
  estado_disponibilidad: string
  categoria: { id: number; nombre: string } | null
  imagen_principal: PublicInmuebleImagen | null
}

export interface PublicInmuebleFilters {
  q: string
  tipo_operacion: PublicTipoOperacion | ''
  page: number
  per_page: 12 | 24
  sort: 'created_at' | 'titulo'
  direction: 'asc' | 'desc'
}

export type PublicInmueblesResponse = LaravelPaginatedResponse<PublicInmuebleListItem>
