import { apiRequest } from './http.ts'
import type { PropietarioListParams, PropietariosResponse } from '../types/propietarios.ts'

export async function listPropietarios(signal?: AbortSignal): Promise<PropietariosResponse> {
  return apiRequest<PropietariosResponse>('/v1/propietarios?per_page=100&sort=nombre_razon_social&direction=asc', { signal })
}

export async function listPropietariosPage(filters: PropietarioListParams, signal?: AbortSignal): Promise<PropietariosResponse> {
  const params = new URLSearchParams()
  const values: Array<[string, string | number | undefined]> = [
    ['q', filters.q],
    ['tipo_persona', filters.tipo_persona],
    ['estado_registro', filters.estado_registro],
    ['page', filters.page],
    ['per_page', filters.per_page],
    ['sort', filters.sort],
    ['direction', filters.direction],
  ]

  values.forEach(([name, value]) => {
    if (value !== undefined && value !== '') {
      params.set(name, String(value))
    }
  })

  return apiRequest<PropietariosResponse>(`/v1/propietarios?${params.toString()}`, { signal })
}
