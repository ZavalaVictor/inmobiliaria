import { useEffect, useState, type PropsWithChildren } from 'react'
import { Sidebar } from './Sidebar.tsx'
import { Topbar } from './Topbar.tsx'

export function AppShell({ children }: PropsWithChildren): React.JSX.Element {
  const [mobileOpen, setMobileOpen] = useState(false)

  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent): void => {
      if (event.key === 'Escape') {
        setMobileOpen(false)
      }
    }
    const handlePopState = (): void => setMobileOpen(false)
    document.addEventListener('keydown', handleKeyDown)
    window.addEventListener('popstate', handlePopState)

    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      window.removeEventListener('popstate', handlePopState)
    }
  }, [])

  return (
    <div className="app-shell min-h-screen">
      {mobileOpen ? <>
        <div aria-hidden="true" className="app-drawer-backdrop md:hidden app-drawer-backdrop-open" onClick={() => setMobileOpen(false)} />
        <div className="app-mobile-drawer app-mobile-drawer-open md:hidden">
          <Sidebar mobile onNavigate={() => setMobileOpen(false)} />
        </div>
      </> : null}
      <div className="md:flex">
        <Sidebar />
        <div className="min-w-0 flex-1 lg:pl-0">
          <Topbar onMenu={() => setMobileOpen(true)} />
          <main className="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8">{children}</main>
        </div>
      </div>
    </div>
  )
}
