import type { ApiResponse } from './api.ts'

export type DashboardRole = 'Administrador' | 'Agente Inmobiliario' | 'Director General'
export type DashboardPeriodKey = 'mes' | 'trimestre' | 'anio'

export interface DashboardPeriod {
  clave: DashboardPeriodKey
  inicio: string
  fin: string
}

export interface DashboardSummary {
  inmuebles_totales?: number
  inmuebles_disponibles?: number
  clientes_totales?: number
  oportunidades_abiertas?: number
  solicitudes_nuevas?: number
  solicitudes_periodo?: number
  solicitudes_en_atencion?: number
  citas_hoy?: number
  citas_proximas?: number
  citas_periodo?: number
  operaciones_registradas?: number
  monto_operaciones_registradas?: number
  inmuebles_asignados?: number
  clientes_asignados?: number
  oportunidades_activas?: number
  operaciones_propias?: number
}

export interface OpportunityStagePoint {
  etapa: string
  total: number
}

export interface PropertyStatePoint {
  estado: string
  total: number
}

export interface OperationsMonthPoint {
  mes: string
  total: number
  monto: number
}

export interface DashboardCharts {
  oportunidades_por_etapa?: OpportunityStagePoint[]
  inmuebles_por_estado?: PropertyStatePoint[]
  operaciones_por_mes?: OperationsMonthPoint[]
}

export interface DashboardRequestItem {
  id: number
  nombre: string
  estado: string
  origen: string
  inmueble: {
    id: number
    codigo: string
    titulo: string
    slug: string
  } | null
  created_at: string | null
}

export interface DashboardAppointmentItem {
  id: number
  fecha_inicio: string | null
  fecha_fin: string | null
  estado: string
  cliente: DashboardPerson | null
  agente: DashboardAgent | null
  inmueble: DashboardProperty | null
}

export interface DashboardOperationItem {
  id: number
  estado: string
  fecha_operacion: string | null
  cliente: DashboardPerson | null
  inmueble: DashboardProperty | null
}

export interface DashboardPerson {
  id: number
  nombres: string
  apellido_paterno: string | null
  apellido_materno: string | null
}

export interface DashboardAgent {
  id: number
  numero_empleado: string | null
  user: DashboardPerson | null
}

export interface DashboardProperty {
  id: number
  codigo: string
  titulo: string
  slug: string
}

export interface DashboardLists {
  solicitudes_recientes?: DashboardRequestItem[]
  citas_proximas?: DashboardAppointmentItem[]
  proximas_citas?: DashboardAppointmentItem[]
  solicitudes_pendientes?: DashboardRequestItem[]
  operaciones_recientes?: DashboardOperationItem[]
}

export interface DashboardData {
  rol: DashboardRole
  periodo: DashboardPeriod
  moneda: 'MXN'
  resumen: DashboardSummary
  graficas: DashboardCharts
  listas: DashboardLists | []
  notificaciones: {
    no_leidas: number
  }
}

export type DashboardResponse = ApiResponse<DashboardData>
