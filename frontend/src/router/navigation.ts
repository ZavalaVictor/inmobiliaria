export function navigate(path: string): void {
  if (window.location.pathname !== path) {
    window.history.pushState({}, '', path)
  }

  window.dispatchEvent(new PopStateEvent('popstate'))
}

export function getInitialRoute(user: { roles: string[] }): string {
  if (user.roles.includes('Administrador')) {
    return '/dashboard'
  }

  if (user.roles.includes('Cliente')) {
    return '/portal-cliente'
  }

  return '/workspace'
}
