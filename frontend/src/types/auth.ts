export type UserRole =
  | 'Administrador'
  | 'Agente Inmobiliario'
  | 'Asistente'
  | 'Director General'
  | 'Cliente'

export interface AuthUser {
  id: number
  nombres: string
  apellidos: string
  email: string
  telefono: string | null
  estado: string
  roles: string[]
  permisos: string[]
}

export type AuthStatus = 'loading' | 'authenticated' | 'unauthenticated' | 'unavailable'
