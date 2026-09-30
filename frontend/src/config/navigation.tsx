import type { ComponentType } from 'react'
import { HomeIcon } from '../components/app/NavIcons.tsx'
import type { AuthUser } from '../types/auth.ts'
import { getInitialRoute } from '../router/navigation.ts'

export type NavigationIcon = ComponentType<{ className?: string }>

export interface NavigationItem {
  label: string
  href: string
  icon: NavigationIcon
  permission?: string
  roles?: string[]
}

export interface NavigationSection {
  label?: string
  items: NavigationItem[]
}

const routePermissions: Record<string, string> = {
  '/dashboard': 'dashboard.ver',
  '/portal-cliente': 'portal_cliente.ver',
}

function canAccessItem(item: NavigationItem, user: AuthUser, can: (permission: string) => boolean): boolean {
  const roleAllowed = !item.roles || item.roles.some((role) => user.roles.includes(role))
  const permissionAllowed = !item.permission || can(item.permission)

  return roleAllowed && permissionAllowed
}

export function getNavigationSections(user: AuthUser | null, can: (permission: string) => boolean): NavigationSection[] {
  if (!user) {
    return []
  }

  const homeHref = getInitialRoute(user)
  const homeItem: NavigationItem = {
    label: 'Inicio',
    href: homeHref,
    icon: HomeIcon,
    permission: routePermissions[homeHref],
  }

  const sections: NavigationSection[] = [
    {
      label: 'INICIO',
      items: [homeItem],
    },
  ]

  return sections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => canAccessItem(item, user, can)),
    }))
    .filter((section) => section.items.length > 0)
}

export function isNavigationItemActive(item: NavigationItem, pathname = window.location.pathname): boolean {
  return pathname === item.href || pathname.startsWith(`${item.href}/`)
}
