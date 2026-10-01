import type { LaravelPaginatedResponse } from './pagination.ts'

export interface CategoriaOption {
  id: number
  nombre: string
}

export type CategoriasResponse = LaravelPaginatedResponse<CategoriaOption>
