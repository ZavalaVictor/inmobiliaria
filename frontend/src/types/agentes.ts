import type { LaravelPaginatedResponse } from './pagination.ts'

export interface AgenteUserResumen {
  id: number
  nombres: string
  apellido_paterno: string
  apellido_materno: string | null
  email: string
  telefono: string | null
  estado: string
}

export interface AgenteListItem {
  id: number
  user_id: number
  numero_empleado: string
  estado_laboral: 'activo' | 'inactivo'
  user: AgenteUserResumen
}

export type AgentesResponse = LaravelPaginatedResponse<AgenteListItem>
