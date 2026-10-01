import { apiRequest } from './http.ts'
import type { AgentesResponse } from '../types/agentes.ts'

export async function listAgentesActivos(signal?: AbortSignal): Promise<AgentesResponse> {
  return apiRequest<AgentesResponse>('/v1/agentes?estado_laboral=activo&per_page=100&sort=numero_empleado&direction=asc', { signal })
}
