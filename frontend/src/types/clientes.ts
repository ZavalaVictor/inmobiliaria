import type { LaravelPaginatedResponse } from './pagination.ts'

export type EstadoCliente = 'prospecto' | 'cliente' | 'inactivo'
export type TipoInteresCliente = 'compra' | 'renta' | 'ambos'
export type NivelInteres = 'bajo' | 'medio' | 'alto'
export type EstadoInteres = 'activo' | 'descartado' | 'convertido'
export type TipoInteraccion = 'llamada' | 'correo' | 'whatsapp' | 'reunion' | 'nota' | 'seguimiento'
export type ClienteSort = 'id' | 'nombres' | 'apellido_paterno' | 'created_at' | 'updated_at'

export interface ClienteUserResumen {
  id: number
  nombres: string
  apellido_paterno: string
  apellido_materno: string | null
  email: string
}

export interface ClienteListItem {
  id: number
  nombres: string
  apellido_paterno: string
  apellido_materno: string | null
  email: string | null
  telefono: string | null
  tipo_interes: TipoInteresCliente | null
  presupuesto_min: string | number | null
  presupuesto_max: string | number | null
  preferencias: string | null
  estado_cliente: EstadoCliente
  created_at: string | null
  updated_at: string | null
  user?: ClienteUserResumen | null
}

export interface ClienteInmuebleResumen {
  id: number
  codigo: string
  titulo: string
  slug: string
}

export interface ClienteInteres {
  id: number
  cliente_id: number
  inmueble_id: number
  nivel_interes: NivelInteres | null
  estado: EstadoInteres
  notas: string | null
  fecha_interes: string | null
  created_at: string | null
  updated_at: string | null
  inmueble: ClienteInmuebleResumen | null
}

export interface ClienteAgentUser {
  id: number
  nombres: string
  apellido_paterno: string
  apellido_materno: string | null
  email: string
  telefono: string | null
  estado: string
}

export interface ClienteAgentAssignment {
  id: number
  cliente_id: number
  agente_id: number
  es_principal: boolean
  fecha_asignacion: string | null
  created_at: string | null
  updated_at: string | null
  agente: {
    id: number
    numero_empleado: string
    user?: ClienteAgentUser
  }
}

export interface ClienteInteraction {
  id: number
  cliente_id: number
  registrado_por_user_id: number | null
  tipo: TipoInteraccion
  descripcion: string
  resultado: string | null
  fecha_interaccion: string | null
  proxima_accion: string | null
  fecha_proxima_accion: string | null
  created_at: string | null
  updated_at: string | null
  registrado_por?: {
    id: number
    nombres: string
    apellido_paterno: string
    apellido_materno: string | null
  } | null
}

export interface ClienteRequestHistory {
  id: number
  inmueble_id: number | null
  cliente_id: number | null
  nombre: string
  email: string
  telefono: string | null
  mensaje: string | null
  medio_preferido: 'telefono' | 'whatsapp' | 'correo' | null
  estado: 'nueva' | 'en_atencion' | 'atendida' | 'descartada'
  origen: string
  fecha_solicitud: string | null
  fecha_atencion: string | null
  inmueble: ClienteInmuebleResumen | null
}

export interface ClienteDetail extends ClienteListItem {
  agentes?: ClienteAgentAssignment[]
  intereses?: ClienteInteres[]
  solicitudes?: ClienteRequestHistory[]
  interacciones?: ClienteInteraction[]
  portal?: {
    configurado: boolean
    habilitado: boolean
    user_id: number | null
  }
}

export interface ClienteListParams {
  q?: string
  estado_cliente?: EstadoCliente
  tipo_interes?: TipoInteresCliente
  agente_id?: number
  page?: number
  per_page?: number
  sort?: ClienteSort
  direction?: 'asc' | 'desc'
}

export interface ClienteWritePayload {
  nombres?: string
  apellido_paterno?: string
  apellido_materno?: string | null
  email?: string | null
  telefono?: string | null
  tipo_interes?: TipoInteresCliente | null
  presupuesto_min?: string | null
  presupuesto_max?: string | null
  preferencias?: string | null
  estado_cliente?: EstadoCliente
}

export interface ClienteInterestPayload {
  inmueble_id?: number
  nivel_interes?: NivelInteres | null
  estado?: EstadoInteres
  notas?: string | null
}

export interface ClienteAssignmentPayload {
  agente_id: number
  es_principal?: boolean
}

export interface ClienteInteractionPayload {
  tipo?: TipoInteraccion
  descripcion?: string
  resultado?: string | null
  fecha_interaccion?: string
  proxima_accion?: string | null
  fecha_proxima_accion?: string | null
}

export type ClientesResponse = LaravelPaginatedResponse<ClienteListItem>
export type ClienteInteresesResponse = LaravelPaginatedResponse<ClienteInteres>
export type ClienteInteraccionesResponse = LaravelPaginatedResponse<ClienteInteraction>
