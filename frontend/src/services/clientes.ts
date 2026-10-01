import { apiRequest } from './http.ts'
import type { ApiResponse } from '../types/api.ts'
import type {
  ClienteAssignmentPayload,
  ClienteAgentAssignment,
  ClienteDetail,
  ClienteInteraction,
  ClienteInteractionPayload,
  ClienteInteraccionesResponse,
  ClienteInterestPayload,
  ClienteInteres,
  ClienteInteresesResponse,
  ClienteListParams,
  ClienteWritePayload,
  ClientesResponse,
} from '../types/clientes.ts'
import type { LaravelPaginatedResponse } from '../types/pagination.ts'

function appendParam(params: URLSearchParams, name: string, value: string | number | undefined): void {
  if (value !== undefined && value !== '') params.set(name, String(value))
}

export async function listClientes(filters: ClienteListParams, signal?: AbortSignal): Promise<ClientesResponse> {
  const params = new URLSearchParams()
  appendParam(params, 'q', filters.q)
  appendParam(params, 'estado_cliente', filters.estado_cliente)
  appendParam(params, 'tipo_interes', filters.tipo_interes)
  appendParam(params, 'agente_id', filters.agente_id)
  appendParam(params, 'page', filters.page)
  appendParam(params, 'per_page', filters.per_page)
  appendParam(params, 'sort', filters.sort)
  appendParam(params, 'direction', filters.direction)
  return apiRequest<ClientesResponse>(`/v1/clientes?${params.toString()}`, { signal })
}

export async function getCliente(id: number, signal?: AbortSignal): Promise<ClienteDetail> {
  const response = await apiRequest<ApiResponse<ClienteDetail>>(`/v1/clientes/${id}`, { signal })
  return response.data
}

export async function createCliente(payload: ClienteWritePayload): Promise<ClienteDetail> {
  const response = await apiRequest<ApiResponse<ClienteDetail>>('/v1/clientes', { method: 'POST', body: payload })
  return response.data
}

export async function updateCliente(id: number, payload: ClienteWritePayload): Promise<ClienteDetail> {
  const response = await apiRequest<ApiResponse<ClienteDetail>>(`/v1/clientes/${id}`, { method: 'PATCH', body: payload })
  return response.data
}

export async function deleteCliente(id: number): Promise<void> {
  await apiRequest<void>(`/v1/clientes/${id}`, { method: 'DELETE' })
}

export async function listClienteIntereses(clienteId: number, signal?: AbortSignal): Promise<ClienteInteresesResponse> {
  return apiRequest<ClienteInteresesResponse>(`/v1/clientes/${clienteId}/intereses?per_page=100&sort=fecha_interes&direction=desc`, { signal })
}

export async function createClienteInteres(clienteId: number, payload: ClienteInterestPayload): Promise<ClienteInteres> {
  const response = await apiRequest<ApiResponse<ClienteInteres>>(`/v1/clientes/${clienteId}/intereses`, { method: 'POST', body: payload })
  return response.data
}

export async function updateClienteInteres(clienteId: number, interestId: number, payload: Omit<ClienteInterestPayload, 'inmueble_id'>): Promise<ClienteInteres> {
  const response = await apiRequest<ApiResponse<ClienteInteres>>(`/v1/clientes/${clienteId}/intereses/${interestId}`, { method: 'PATCH', body: payload })
  return response.data
}

export async function deleteClienteInteres(clienteId: number, interestId: number): Promise<void> {
  await apiRequest<void>(`/v1/clientes/${clienteId}/intereses/${interestId}`, { method: 'DELETE' })
}

export async function listClienteAgentes(clienteId: number, signal?: AbortSignal): Promise<ApiResponse<ClienteAgentAssignment[]>> {
  return apiRequest<ApiResponse<ClienteAgentAssignment[]>>(`/v1/clientes/${clienteId}/agentes`, { signal })
}

export async function assignClienteAgente(clienteId: number, payload: ClienteAssignmentPayload): Promise<ClienteAgentAssignment> {
  const response = await apiRequest<ApiResponse<ClienteAgentAssignment>>(`/v1/clientes/${clienteId}/agentes`, { method: 'POST', body: payload })
  return response.data
}

export async function setPrincipalClienteAgente(clienteId: number, assignmentId: number): Promise<ClienteAgentAssignment> {
  const response = await apiRequest<ApiResponse<ClienteAgentAssignment>>(`/v1/clientes/${clienteId}/agentes/${assignmentId}/principal`, { method: 'PATCH', body: {} })
  return response.data
}

export async function deleteClienteAgente(clienteId: number, assignmentId: number): Promise<void> {
  await apiRequest<void>(`/v1/clientes/${clienteId}/agentes/${assignmentId}`, { method: 'DELETE' })
}

export async function listClienteInteracciones(clienteId: number, signal?: AbortSignal): Promise<ClienteInteraccionesResponse> {
  return apiRequest<ClienteInteraccionesResponse>(`/v1/clientes/${clienteId}/interacciones?per_page=100&sort=fecha_interaccion&direction=desc`, { signal })
}

export async function createClienteInteraccion(clienteId: number, payload: ClienteInteractionPayload): Promise<ClienteInteraction> {
  const response = await apiRequest<ApiResponse<ClienteInteraction>>(`/v1/clientes/${clienteId}/interacciones`, { method: 'POST', body: payload })
  return response.data
}

export async function updateClienteInteraccion(clienteId: number, interactionId: number, payload: ClienteInteractionPayload): Promise<ClienteInteraction> {
  const response = await apiRequest<ApiResponse<ClienteInteraction>>(`/v1/clientes/${clienteId}/interacciones/${interactionId}`, { method: 'PATCH', body: payload })
  return response.data
}

export async function deleteClienteInteraccion(clienteId: number, interactionId: number): Promise<void> {
  await apiRequest<void>(`/v1/clientes/${clienteId}/interacciones/${interactionId}`, { method: 'DELETE' })
}

export async function listClienteSolicitudes(clienteId: number, signal?: AbortSignal): Promise<LaravelPaginatedResponse<NonNullable<ClienteDetail['solicitudes']>[number]>> {
  return apiRequest<LaravelPaginatedResponse<NonNullable<ClienteDetail['solicitudes']>[number]>>(`/v1/solicitudes?cliente_id=${clienteId}&per_page=100&sort=fecha_solicitud&direction=desc`, { signal })
}

export async function enableClientePortal(clienteId: number): Promise<unknown> {
  return apiRequest<unknown>(`/v1/clientes/${clienteId}/portal/habilitar`, { method: 'POST', body: {} })
}

export async function disableClientePortal(clienteId: number): Promise<unknown> {
  return apiRequest<unknown>(`/v1/clientes/${clienteId}/portal/deshabilitar`, { method: 'PATCH', body: {} })
}
