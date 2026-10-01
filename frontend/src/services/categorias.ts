import { apiRequest } from './http.ts'
import type { CategoriaOption, CategoriasResponse } from '../types/categorias.ts'

export async function listCategorias(signal?: AbortSignal): Promise<CategoriasResponse> {
  return apiRequest<CategoriasResponse>('/v1/categorias?per_page=100&sort=nombre&direction=asc', { signal })
}

export function getCategoriaOptions(response: CategoriasResponse): CategoriaOption[] {
  return response.data
}
