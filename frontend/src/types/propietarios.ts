import type { LaravelPaginatedResponse } from './pagination.ts'

export type TipoPersonaPropietario = 'fisica' | 'moral'
export type EstadoRegistroPropietario = 'activo' | 'inactivo'
export type PropietarioSort = 'id' | 'nombre_razon_social' | 'rfc' | 'created_at' | 'updated_at'

export interface PropietarioOption {
  id: number
  nombre_razon_social: string
}

export interface PropietarioListItem extends PropietarioOption {
  tipo_persona: TipoPersonaPropietario
  rfc: string
  telefono: string
  email: string | null
  direccion: string
  estado_registro: EstadoRegistroPropietario
  created_at: string | null
  updated_at: string | null
}

export interface PropietarioListParams {
  q?: string
  tipo_persona?: TipoPersonaPropietario
  estado_registro?: EstadoRegistroPropietario
  page?: number
  per_page?: number
  sort?: PropietarioSort
  direction?: 'asc' | 'desc'
}

export interface PropietarioFilters {
  q: string
  tipo_persona: TipoPersonaPropietario | ''
  estado_registro: EstadoRegistroPropietario | ''
  page: number
  per_page: 15 | 25 | 50
  sort: PropietarioSort
  direction: 'asc' | 'desc'
}

export type PropietariosResponse = LaravelPaginatedResponse<PropietarioListItem>
