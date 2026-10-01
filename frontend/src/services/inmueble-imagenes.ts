import { apiRequest } from './http.ts'
import type { ApiResponse } from '../types/api.ts'
import type {
  InmuebleImagen,
  InmuebleImagenUpdatePayload,
  ReordenarInmuebleImagenesPayload,
} from '../types/inmueble-imagenes.ts'

export async function listInmuebleImagenes(inmuebleId: number, signal?: AbortSignal): Promise<InmuebleImagen[]> {
  const response = await apiRequest<ApiResponse<InmuebleImagen[]>>(`/v1/inmuebles/${inmuebleId}/imagenes`, { signal })
  return response.data
}

export async function uploadInmuebleImagen(
  inmuebleId: number,
  imagen: File,
  textoAlternativo?: string,
): Promise<InmuebleImagen> {
  const formData = new FormData()
  formData.append('imagen', imagen)

  if (textoAlternativo !== undefined) {
    formData.append('texto_alternativo', textoAlternativo)
  }

  const response = await apiRequest<ApiResponse<InmuebleImagen>>(`/v1/inmuebles/${inmuebleId}/imagenes`, {
    method: 'POST',
    body: formData,
  })

  return response.data
}

export async function updateInmuebleImagen(
  inmuebleId: number,
  imagenId: number,
  payload: InmuebleImagenUpdatePayload,
): Promise<InmuebleImagen> {
  const response = await apiRequest<ApiResponse<InmuebleImagen>>(`/v1/inmuebles/${inmuebleId}/imagenes/${imagenId}`, {
    method: 'PATCH',
    body: payload,
  })

  return response.data
}

export async function setInmuebleImagenPrincipal(inmuebleId: number, imagenId: number): Promise<InmuebleImagen> {
  const response = await apiRequest<ApiResponse<InmuebleImagen>>(
    `/v1/inmuebles/${inmuebleId}/imagenes/${imagenId}/principal`,
    { method: 'PATCH' },
  )

  return response.data
}

export async function reorderInmuebleImagenes(
  inmuebleId: number,
  payload: ReordenarInmuebleImagenesPayload,
): Promise<InmuebleImagen[]> {
  const response = await apiRequest<ApiResponse<InmuebleImagen[]>>(`/v1/inmuebles/${inmuebleId}/imagenes/reordenar`, {
    method: 'PATCH',
    body: payload,
  })

  return response.data
}

export async function deleteInmuebleImagen(inmuebleId: number, imagenId: number): Promise<void> {
  await apiRequest<void>(`/v1/inmuebles/${inmuebleId}/imagenes/${imagenId}`, { method: 'DELETE' })
}
