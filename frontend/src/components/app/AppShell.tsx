import { useEffect, useState, type PropsWithChildren } from 'react'
import { useAuth } from '../../hooks/useAuth.ts'
import { getPrimaryRole } from '../../router/navigation.ts'
import { Sidebar } from './Sidebar.tsx'
import { Topbar } from './Topbar.tsx'

function getPageTitle(pathname: string, role: string): string {
  if (pathname === '/dashboard') {
    return role === 'Administrador' ? 'Dashboard administrativo' : 'Dashboard'
  }

  return 'Área de trabajo'
}

export function AppShell({ children }: PropsWithChildren): React.JSX.Element {
  const { user } = useAuth()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [pathname, setPathname] = useState(() => window.location.pathname)
  const role = getPrimaryRole(user?.roles ?? [])

  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent): void => {
      if (event.key === 'Escape') {
        setMobileOpen(false)
      }
    }
    const handlePopState = (): void => {
      setPathname(window.location.pathname)
      setMobileOpen(false)
    }
    document.addEventListener('keydown', handleKeyDown)
    window.addEventListener('popstate', handlePopState)

    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      window.removeEventListener('popstate', handlePopState)
    }
  }, [])

  return (
    <div className="app-shell min-h-screen">
      <div aria-hidden={!mobileOpen} className={`app-drawer-backdrop lg:hidden ${mobileOpen ? 'app-drawer-backdrop-open' : ''}`} onClick={() => setMobileOpen(false)} />
      <div className={`app-mobile-drawer lg:hidden ${mobileOpen ? 'app-mobile-drawer-open' : ''}`} inert={!mobileOpen}>
        <Sidebar mobile onNavigate={() => setMobileOpen(false)} />
      </div>
      <div className="lg:flex">
        <Sidebar />
        <div className="min-w-0 flex-1 lg:pl-0">
          <Topbar onMenu={() => setMobileOpen(true)} title={getPageTitle(pathname, role)} />
          <main className="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8">{children}</main>
        </div>
      </div>
    </div>
  )
}
