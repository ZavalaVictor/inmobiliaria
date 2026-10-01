import type { LaravelPaginatedResponse } from './pagination.ts'

export type EstadoSolicitud = 'nueva' | 'en_atencion' | 'atendida' | 'descartada'
export type MedioSolicitud = 'telefono' | 'whatsapp' | 'correo'
export type SolicitudSort = 'id' | 'nombre' | 'email' | 'estado' | 'origen' | 'fecha_solicitud' | 'fecha_atencion' | 'created_at' | 'updated_at'
export type SortDirection = 'asc' | 'desc'

export interface SolicitudClienteResumen {
  id: number
  nombres: string
  apellido_paterno: string
  apellido_materno: string | null
}

export interface SolicitudInmuebleResumen {
  id: number
  codigo: string
  titulo: string
  slug: string
  publicado: boolean
}

export interface SolicitudAtendidaPorResumen {
  id: number
  nombres: string
  apellido_paterno: string
  apellido_materno: string | null
}

export interface SolicitudInformacion {
  id: number
  inmueble_id: number | null
  cliente_id: number | null
  atendida_por_user_id: number | null
  nombre: string
  email: string
  telefono: string | null
  mensaje: string | null
  medio_preferido: MedioSolicitud | null
  estado: EstadoSolicitud
  origen: string
  fecha_solicitud: string | null
  fecha_atencion: string | null
  created_at: string | null
  updated_at: string | null
  cliente: SolicitudClienteResumen | null
  inmueble: SolicitudInmuebleResumen | null
  atendida_por: SolicitudAtendidaPorResumen | null
}

export interface SolicitudListParams {
  q?: string
  estado?: EstadoSolicitud
  medio_preferido?: MedioSolicitud
  origen?: string
  page?: number
  per_page?: number
  sort?: SolicitudSort
  direction?: SortDirection
}

export interface SolicitudWritePayload {
  nombre?: string
  email?: string
  telefono?: string | null
  mensaje?: string | null
  medio_preferido?: MedioSolicitud | null
  estado?: EstadoSolicitud
  atendida_por_user_id?: number | null
  fecha_atencion?: string | null
}

export interface SolicitudCreatePayload {
  nombre: string
  email: string
  telefono?: string | null
  mensaje?: string | null
  medio_preferido?: MedioSolicitud | null
  inmueble_id?: number | null
}

export interface SolicitudConvertResponse {
  cliente: {
    id: number
    nombres: string
    apellido_paterno: string
    apellido_materno: string | null
    email: string | null
    telefono: string | null
    estado_cliente: string
  }
  creado: boolean
}

export type SolicitudesResponse = LaravelPaginatedResponse<SolicitudInformacion>
