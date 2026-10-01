import type { ComponentType } from 'react'
import { CalendarIcon, DocumentIcon, HomeIcon, KanbanIcon, MailIcon, MapIcon, OpportunitiesIcon, OperationIcon, OwnerIcon, PropertyIcon, RequestIcon, SettingsIcon, UsersIcon } from '../components/app/NavIcons.tsx'
import type { AuthUser } from '../types/auth.ts'
import { getInitialRoute } from '../router/navigation.ts'

export type NavigationIcon = ComponentType<{ className?: string }>

export interface NavigationItem {
  label: string
  href: string
  icon: NavigationIcon
  enabled?: boolean
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

const internalRoles = ['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']

function canAccessItem(item: NavigationItem, user: AuthUser, can: (permission: string) => boolean): boolean {
  const roleAllowed = !item.roles || item.roles.some((role) => user.roles.includes(role))
  const permissionAllowed = !item.permission || can(item.permission)

  return item.enabled !== false && roleAllowed && permissionAllowed
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
    enabled: true,
    permission: routePermissions[homeHref],
  }

  const sections: NavigationSection[] = [
    {
      label: 'INICIO',
      items: [homeItem],
    },
    {
      label: 'CRM',
      items: [
        { label: 'Clientes', href: '/clientes', icon: UsersIcon, enabled: false, permission: 'clientes.ver', roles: internalRoles },
        { label: 'Propietarios', href: '/propietarios', icon: OwnerIcon, enabled: true, permission: 'propietarios.ver', roles: internalRoles },
        { label: 'Solicitudes', href: '/solicitudes', icon: RequestIcon, enabled: true, permission: 'solicitudes.ver', roles: internalRoles },
      ],
    },
    {
      label: 'INMUEBLES',
      items: [
        { label: 'Inmuebles', href: '/inmuebles', icon: PropertyIcon, enabled: true, permission: 'inmuebles.ver', roles: internalRoles },
        { label: 'Mapa', href: '/inmuebles/mapa', icon: MapIcon, enabled: false, permission: 'inmuebles.ver', roles: internalRoles },
      ],
    },
    {
      label: 'COMERCIAL',
      items: [
        { label: 'Oportunidades', href: '/oportunidades', icon: OpportunitiesIcon, enabled: false, permission: 'oportunidades.ver', roles: internalRoles },
        { label: 'Kanban', href: '/oportunidades/kanban', icon: KanbanIcon, enabled: false, permission: 'oportunidades.ver', roles: internalRoles },
        { label: 'Operaciones', href: '/operaciones', icon: OperationIcon, enabled: false, permission: 'operaciones.ver', roles: internalRoles },
      ],
    },
    {
      label: 'AGENDA',
      items: [
        { label: 'Citas', href: '/citas', icon: CalendarIcon, enabled: false, permission: 'citas.ver', roles: internalRoles },
        { label: 'Calendario', href: '/citas/calendario', icon: CalendarIcon, enabled: false, permission: 'citas.ver', roles: internalRoles },
      ],
    },
    {
      label: 'GESTIÓN',
      items: [
        { label: 'Documentos', href: '/documentos', icon: DocumentIcon, enabled: false, permission: 'documentos.ver', roles: internalRoles },
        { label: 'Historial de correos', href: '/historial-correos', icon: MailIcon, enabled: false, permission: 'historial_correos.ver', roles: internalRoles },
      ],
    },
    {
      label: 'ADMINISTRACIÓN',
      items: [
        { label: 'Usuarios', href: '/usuarios', icon: UsersIcon, enabled: false, permission: 'usuarios.ver', roles: ['Administrador'] },
        { label: 'Agentes', href: '/agentes', icon: UsersIcon, enabled: false, permission: 'agentes.ver', roles: ['Administrador'] },
        { label: 'Configuración', href: '/configuracion', icon: SettingsIcon, enabled: false, permission: 'roles.ver', roles: ['Administrador'] },
      ],
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
