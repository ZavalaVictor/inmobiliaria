import type { ApiResponse } from './api.ts'

export interface LaravelPaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface LaravelPaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  path: string
  per_page: number
  to: number | null
  total: number
}

export type LaravelPaginatedResponse<T> = ApiResponse<T[]> & {
  links: LaravelPaginationLinks
  meta: LaravelPaginationMeta
}
