import { publicApiRequest } from './http.ts'
import type { PublicFilters, PublicPropertiesResponse, PublicProperty } from './types.ts'

function append(params: URLSearchParams, key: string, value: string | number): void {
  if (value !== '') params.set(key, String(value))
}

export async function listProperties(filters: PublicFilters, signal?: AbortSignal): Promise<PublicPropertiesResponse> {
  const params = new URLSearchParams()
  append(params, 'q', filters.q)
  append(params, 'ubicacion', filters.ubicacion)
  append(params, 'tipo_inmueble', filters.tipo_inmueble)
  append(params, 'tipo_operacion', filters.tipo_operacion)
  append(params, 'precio_min', filters.precio_min)
  append(params, 'precio_max', filters.precio_max)
  append(params, 'page', filters.page)
  append(params, 'per_page', filters.per_page)
  append(params, 'sort', filters.sort)
  append(params, 'direction', filters.direction)
  return publicApiRequest<PublicPropertiesResponse>(`/v1/public/inmuebles?${params.toString()}`, { signal })
}

export async function getProperty(slug: string, signal?: AbortSignal): Promise<PublicProperty> {
  const response = await publicApiRequest<{ data: PublicProperty }>(`/v1/public/inmuebles/${encodeURIComponent(slug)}`, { signal })
  return response.data
}

export async function recordPropertyView(id: number): Promise<void> {
  await publicApiRequest<void>(`/v1/public/inmuebles/${id}/visualizaciones`, { method: 'POST', body: JSON.stringify({}) })
}

export interface PublicInquiryPayload {
  nombre: string
  email: string
  telefono?: string
  mensaje?: string
  medio_preferido?: 'correo' | 'telefono' | 'whatsapp'
  inmueble_id?: number
}

export async function submitInquiry(payload: PublicInquiryPayload): Promise<void> {
  await publicApiRequest<void>('/v1/public/solicitudes', { method: 'POST', body: JSON.stringify(payload) })
}
