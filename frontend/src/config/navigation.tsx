import type { ComponentType } from 'react'
import { DashboardIcon, WorkspaceIcon } from '../components/app/NavIcons.tsx'
import type { AuthUser } from '../types/auth.ts'

export type NavigationIcon = ComponentType<{ className?: string }>

export interface NavigationItem {
  label: string
  href: string
  icon: NavigationIcon
  permission?: string
  roles?: string[]
}

export const navigationItems: NavigationItem[] = [
  {
    label: 'Dashboard',
    href: '/dashboard',
    icon: DashboardIcon,
    permission: 'dashboard.ver',
    roles: ['Administrador', 'Agente Inmobiliario', 'Director General'],
  },
  {
    label: 'Área de trabajo',
    href: '/workspace',
    icon: WorkspaceIcon,
    roles: ['Asistente'],
  },
]

export function getNavigationItems(user: AuthUser | null, can: (permission: string) => boolean): NavigationItem[] {
  if (!user) {
    return []
  }

  return navigationItems.filter((item) => {
    const roleAllowed = !item.roles || item.roles.some((role) => user.roles.includes(role))
    const permissionAllowed = !item.permission || can(item.permission)
    return roleAllowed && permissionAllowed
  })
}
