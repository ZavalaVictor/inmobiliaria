import { createContext } from 'react'
import type { AuthStatus, AuthUser } from '../types/auth.ts'

export interface AuthContextValue {
  user: AuthUser | null
  status: AuthStatus
  notice: string | null
  bootstrapError: string | null
  login: (email: string, password: string) => Promise<AuthUser>
  logout: () => Promise<void>
  can: (permission: string) => boolean
  clearNotice: () => void
}

export const authContext = createContext<AuthContextValue | null>(null)
