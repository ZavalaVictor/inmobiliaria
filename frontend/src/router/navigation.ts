export function navigate(path: string): void {
  if (window.location.pathname !== path) {
    window.history.pushState({}, '', path)
  }

  window.dispatchEvent(new PopStateEvent('popstate'))
}

const ROLE_PRECEDENCE = [
  'Administrador',
  'Agente Inmobiliario',
  'Director General',
  'Asistente',
  'Cliente',
]

export function getPrimaryRole(roles: string[]): string {
  return ROLE_PRECEDENCE.find((role) => roles.includes(role)) ?? 'Usuario autenticado'
}

export function getInitialRoute(user: { roles: string[]; permisos?: string[] }): string {
  const strategicRole = ['Administrador', 'Agente Inmobiliario', 'Director General']
  if (strategicRole.some((role) => user.roles.includes(role)) && user.permisos?.includes('dashboard.ver')) {
    return '/dashboard'
  }

  if (user.roles.includes('Cliente')) {
    return '/portal-cliente'
  }

  return '/workspace'
}
