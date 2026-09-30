import { useEffect, useRef, useState } from 'react'
import { useAuth } from '../../hooks/useAuth.ts'
import { getPrimaryRole, getInitialRoute, navigate } from '../../router/navigation.ts'
import { useTheme } from '../../hooks/useTheme.ts'
import { NotificationBadge } from './NotificationBadge.tsx'

interface TopbarProps {
  onMenu: () => void
}

function MenuIcon({ className }: { className?: string }): React.JSX.Element {
  return <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" /></svg>
}

function SunIcon({ className }: { className?: string }): React.JSX.Element {
  return <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.5" stroke="currentColor" strokeWidth="1.8" /><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" /></svg>
}

function MoonIcon({ className }: { className?: string }): React.JSX.Element {
  return <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24"><path d="M20 15.2A8.5 8.5 0 0 1 8.8 4a8.5 8.5 0 1 0 11.2 11.2Z" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" /></svg>
}

function MonitorIcon({ className }: { className?: string }): React.JSX.Element {
  return <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24"><rect height="13" rx="1.5" stroke="currentColor" strokeWidth="1.8" width="18" x="3" y="3" /><path d="M8 21h8M12 16v5" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" /></svg>
}

function HelpIcon({ className }: { className?: string }): React.JSX.Element {
  return <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5" stroke="currentColor" strokeWidth="1.8" /><path d="M9.7 9.2a2.5 2.5 0 1 1 4.2 1.8c-1.1.9-1.9 1.3-1.9 2.8M12 17.1v.1" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" /></svg>
}

export function Topbar({ onMenu }: TopbarProps): React.JSX.Element {
  const { logout, user } = useAuth()
  const { cycleMode, mode } = useTheme()
  const [menuOpen, setMenuOpen] = useState(false)
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handleDocumentClick = (event: MouseEvent): void => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setMenuOpen(false)
      }
    }
    const handleEscape = (event: KeyboardEvent): void => {
      if (event.key === 'Escape') {
        setMenuOpen(false)
      }
    }
    document.addEventListener('click', handleDocumentClick)
    document.addEventListener('keydown', handleEscape)

    return () => {
      document.removeEventListener('click', handleDocumentClick)
      document.removeEventListener('keydown', handleEscape)
    }
  }, [])

  async function handleLogout(): Promise<void> {
    await logout()
  }

  const themeLabel = mode === 'light' ? 'Claro' : mode === 'dark' ? 'Oscuro' : 'Sistema'
  const ThemeIcon = mode === 'light' ? SunIcon : mode === 'dark' ? MoonIcon : MonitorIcon

  return (
    <header className="app-topbar sticky top-0 z-20 flex min-h-16 items-center gap-4 border-b px-4 py-3 sm:px-6 lg:grid lg:grid-cols-[1fr_auto_1fr] lg:px-8">
      <div className="flex min-w-0 items-center gap-3">
        <button aria-label="Abrir menú de navegación" className="app-icon-button app-menu-toggle md:hidden" onClick={onMenu} type="button"><MenuIcon className="size-5" /></button>
      </div>
      <div className="ml-auto flex shrink-0 items-center gap-2 sm:gap-3 lg:justify-self-end">
        <NotificationBadge />
        <button aria-label="Ayuda" className="app-icon-button hidden sm:inline-flex" title="Ayuda" type="button"><HelpIcon className="size-5" /></button>
        <div className="relative" ref={menuRef}>
          <button aria-expanded={menuOpen} aria-haspopup="menu" className="app-user-button" onClick={() => setMenuOpen((current) => !current)} type="button">
            <span className="app-avatar">{user?.nombres.slice(0, 1).toUpperCase() ?? 'U'}</span>
            <span className="hidden text-left md:block"><span className="block max-w-36 truncate text-sm font-semibold text-[var(--app-text)]">{user?.nombres} {user?.apellidos}</span><span className="block text-xs text-[var(--app-text-muted)]">{getPrimaryRole(user?.roles ?? [])}</span></span>
            <span aria-hidden="true" className="hidden text-[var(--app-text-muted)] md:block">⌄</span>
          </button>
          {menuOpen ? <div className="app-user-menu" role="menu">
            <div className="border-b border-[var(--app-border)] px-4 py-3 md:hidden"><p className="truncate text-sm font-semibold text-[var(--app-text)]">{user?.nombres} {user?.apellidos}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{getPrimaryRole(user?.roles ?? [])}</p></div>
            <button className="app-menu-item" onClick={cycleMode} role="menuitem" type="button"><ThemeIcon className="size-4" /> Tema: {themeLabel}</button>
            <button className="app-menu-item" onClick={() => { setMenuOpen(false); navigate(user ? getInitialRoute(user) : '/login') }} role="menuitem" type="button">Ir a inicio</button>
            <button className="app-menu-item app-menu-item-danger" onClick={handleLogout} role="menuitem" type="button">Cerrar sesión</button>
          </div> : null}
        </div>
      </div>
    </header>
  )
}
