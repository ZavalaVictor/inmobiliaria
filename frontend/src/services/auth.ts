import { apiRequest } from './http.ts'
import type { AuthUser } from '../types/auth.ts'

interface ApiResource<T> {
  data: T
}

export interface ResetPasswordPayload {
  email: string
  token: string
  password: string
  password_confirmation: string
}

function unwrapUser(response: ApiResource<AuthUser> | AuthUser): AuthUser {
  return 'data' in response ? response.data : response
}

export async function login(email: string, password: string): Promise<AuthUser> {
  const response = await apiRequest<ApiResource<AuthUser>>('/v1/auth/login', {
    method: 'POST',
    body: { email, password },
    handleUnauthorized: false,
  })

  return unwrapUser(response)
}

export async function getCurrentUser(): Promise<AuthUser> {
  const response = await apiRequest<ApiResource<AuthUser>>('/v1/auth/me')
  return unwrapUser(response)
}

export async function logout(): Promise<void> {
  await apiRequest('/v1/auth/logout', { method: 'POST', handleUnauthorized: false })
}

export async function forgotPassword(email: string): Promise<void> {
  await apiRequest('/v1/auth/forgot-password', {
    method: 'POST',
    body: { email },
    handleUnauthorized: false,
  })
}

export async function resetPassword(payload: ResetPasswordPayload): Promise<void> {
  await apiRequest('/v1/auth/reset-password', {
    method: 'POST',
    body: payload,
    handleUnauthorized: false,
  })
}
