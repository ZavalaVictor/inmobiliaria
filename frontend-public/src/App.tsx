import { lazy, Suspense, useEffect, useState } from 'react'
import { PropertyDetailPage } from './pages/PropertyDetailPage.tsx'
import { PropertiesPage } from './pages/PropertiesPage.tsx'
import { ContactPage } from './pages/ContactPage.tsx'
import { PrivacyPage } from './pages/PrivacyPage.tsx'

const GlobeExplorerPage = lazy(() => import('./pages/GlobeExplorerPage.tsx').then(({ GlobeExplorerPage: page }) => ({ default: page })))

function getInitialTheme(): 'light' | 'dark' {
  const saved = window.localStorage.getItem('sotytech-public-theme')
  return saved === 'dark' || saved === 'light' ? saved : window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function PublicNotFound(): React.JSX.Element { return <div className="public-page min-h-screen"><main className="public-container grid min-h-screen place-items-center py-16"><section className="public-card max-w-lg p-8 text-center"><p className="public-kicker text-[var(--public-accent)]">SOTYTECH</p><h1 className="mt-4 text-5xl font-black text-[var(--public-text)]">404</h1><p className="mt-3 text-[var(--public-muted)]">No encontramos esta página pública.</p><a className="public-button-primary mt-6" href="/propiedades">Ver propiedades</a></section></main></div> }

export default function App(): React.JSX.Element {
  const [pathname, setPathname] = useState(window.location.pathname)
  const [theme, setTheme] = useState<'light' | 'dark'>(getInitialTheme)

  useEffect(() => {
    const onPopState = (): void => setPathname(window.location.pathname)
    window.addEventListener('popstate', onPopState)
    return () => window.removeEventListener('popstate', onPopState)
  }, [])

  useEffect(() => { document.documentElement.dataset.publicTheme = theme; window.localStorage.setItem('sotytech-public-theme', theme) }, [theme])

  const toggleTheme = (): void => setTheme((current) => current === 'dark' ? 'light' : 'dark')
  const detailMatch = pathname.match(/^\/propiedades\/([^/]+)$/)

  if (pathname === '/propiedades' || pathname === '/') return <PropertiesPage onToggleTheme={toggleTheme} theme={theme} />
  if (pathname === '/contacto') return <ContactPage onToggleTheme={toggleTheme} theme={theme} />
  if (pathname === '/explorar') return <Suspense fallback={<div className="public-page min-h-screen"><main className="public-container grid min-h-screen place-items-center py-16"><p className="text-sm font-bold text-[var(--public-muted)]">Preparando la exploración 3D...</p></main></div>}><GlobeExplorerPage onToggleTheme={toggleTheme} theme={theme} /></Suspense>
  if (pathname === '/aviso-de-privacidad') return <PrivacyPage onToggleTheme={toggleTheme} theme={theme} />
  if (detailMatch) return <PropertyDetailPage onToggleTheme={toggleTheme} slug={decodeURIComponent(detailMatch[1])} theme={theme} />
  return <PublicNotFound />
}
