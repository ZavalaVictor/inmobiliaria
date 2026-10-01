import { apiRequest } from './http.ts'
import type { PublicInmuebleFilters, PublicInmueblesResponse } from '../types/public-inmuebles.ts'

function appendParam(params: URLSearchParams, name: string, value: string | number): void {
  if (value !== '') {
    params.set(name, String(value))
  }
}

export async function listPublicInmuebles(filters: PublicInmuebleFilters, signal?: AbortSignal): Promise<PublicInmueblesResponse> {
  const params = new URLSearchParams()

  appendParam(params, 'q', filters.q)
  appendParam(params, 'tipo_operacion', filters.tipo_operacion)
  appendParam(params, 'page', filters.page)
  appendParam(params, 'per_page', filters.per_page)
  appendParam(params, 'sort', filters.sort)
  appendParam(params, 'direction', filters.direction)

  return apiRequest<PublicInmueblesResponse>(`/v1/public/inmuebles?${params.toString()}`, { signal, handleUnauthorized: false })
}
