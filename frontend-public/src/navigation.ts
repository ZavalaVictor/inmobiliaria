export function navigate(path: string): void {
  if (window.location.pathname + window.location.search !== path) window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

export function adminUrl(path = '/login'): string {
  return `${(import.meta.env.VITE_ADMIN_URL?.trim() || 'http://localhost:5173').replace(/\/$/, '')}${path}`
}
