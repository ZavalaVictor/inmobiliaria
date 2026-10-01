import { apiRequest } from './http.ts'
import type { ApiResponse } from '../types/api.ts'
import type { InmuebleDetail, InmuebleListParams, InmuebleWritePayload, InmueblesResponse } from '../types/inmuebles.ts'

function appendParam(params: URLSearchParams, name: string, value: string | number | boolean | undefined): void {
  if (value !== undefined && value !== '') {
    params.set(name, typeof value === 'boolean' ? (value ? '1' : '0') : String(value))
  }
}

export async function listInmuebles(filters: InmuebleListParams, signal?: AbortSignal): Promise<InmueblesResponse> {
  const params = new URLSearchParams()

  appendParam(params, 'q', filters.q)
  appendParam(params, 'tipo_operacion', filters.tipo_operacion)
  appendParam(params, 'estado_disponibilidad', filters.estado_disponibilidad)
  appendParam(params, 'categoria_id', filters.categoria_id)
  appendParam(params, 'propietario_id', filters.propietario_id)
  appendParam(params, 'habitaciones', filters.habitaciones)
  appendParam(params, 'banos_completos', filters.banos_completos)
  appendParam(params, 'publicado', filters.publicado)
  appendParam(params, 'municipio', filters.municipio)
  appendParam(params, 'estado_ubicacion', filters.estado_ubicacion)
  appendParam(params, 'page', filters.page)
  appendParam(params, 'per_page', filters.per_page)
  appendParam(params, 'sort', filters.sort)
  appendParam(params, 'direction', filters.direction)

  const query = params.toString()
  return apiRequest<InmueblesResponse>(`/v1/inmuebles${query ? `?${query}` : ''}`, { signal })
}

export async function getInmueble(id: number, signal?: AbortSignal): Promise<InmuebleDetail> {
  const response = await apiRequest<ApiResponse<InmuebleDetail>>(`/v1/inmuebles/${id}`, { signal })
  return response.data
}

export async function createInmueble(payload: InmuebleWritePayload): Promise<InmuebleDetail> {
  const response = await apiRequest<ApiResponse<InmuebleDetail>>('/v1/inmuebles', { method: 'POST', body: payload })
  return response.data
}

export async function updateInmueble(id: number, payload: InmuebleWritePayload): Promise<InmuebleDetail> {
  const response = await apiRequest<ApiResponse<InmuebleDetail>>(`/v1/inmuebles/${id}`, { method: 'PATCH', body: payload })
  return response.data
}

export async function deleteInmueble(id: number): Promise<void> {
  await apiRequest<void>(`/v1/inmuebles/${id}`, { method: 'DELETE' })
}
