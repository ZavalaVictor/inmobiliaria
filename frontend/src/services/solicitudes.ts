import { apiRequest } from './http.ts'
import type { ApiResponse } from '../types/api.ts'
import type { SolicitudConvertResponse, SolicitudCreatePayload, SolicitudInformacion, SolicitudListParams, SolicitudWritePayload, SolicitudesResponse } from '../types/solicitudes.ts'

function appendParam(params: URLSearchParams, name: string, value: string | number | undefined): void {
  if (value !== undefined && value !== '') {
    params.set(name, String(value))
  }
}

export async function listSolicitudes(filters: SolicitudListParams, signal?: AbortSignal): Promise<SolicitudesResponse> {
  const params = new URLSearchParams()
  appendParam(params, 'q', filters.q)
  appendParam(params, 'estado', filters.estado)
  appendParam(params, 'medio_preferido', filters.medio_preferido)
  appendParam(params, 'origen', filters.origen)
  appendParam(params, 'page', filters.page)
  appendParam(params, 'per_page', filters.per_page)
  appendParam(params, 'sort', filters.sort)
  appendParam(params, 'direction', filters.direction)
  return apiRequest<SolicitudesResponse>(`/v1/solicitudes?${params.toString()}`, { signal })
}

export async function getSolicitud(id: number, signal?: AbortSignal): Promise<SolicitudInformacion> {
  const response = await apiRequest<ApiResponse<SolicitudInformacion>>(`/v1/solicitudes/${id}`, { signal })
  return response.data
}

export async function createSolicitud(payload: SolicitudCreatePayload): Promise<SolicitudInformacion> {
  const response = await apiRequest<ApiResponse<SolicitudInformacion>>('/v1/solicitudes', { method: 'POST', body: payload })
  return response.data
}

export async function updateSolicitud(id: number, payload: SolicitudWritePayload): Promise<SolicitudInformacion> {
  const response = await apiRequest<ApiResponse<SolicitudInformacion>>(`/v1/solicitudes/${id}`, { method: 'PATCH', body: payload })
  return response.data
}

export async function deleteSolicitud(id: number): Promise<void> {
  await apiRequest<void>(`/v1/solicitudes/${id}`, { method: 'DELETE' })
}

export async function convertSolicitudToCliente(id: number): Promise<SolicitudConvertResponse> {
  const response = await apiRequest<ApiResponse<SolicitudConvertResponse>>(`/v1/solicitudes/${id}/convertir-cliente`, { method: 'POST' })
  return response.data
}
