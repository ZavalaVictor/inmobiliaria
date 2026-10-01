import { useEffect, useState } from 'react'
import { navigate } from '../navigation.ts'
import brandLogoAsset from '../../../frontend/src/assets/branding/logo-sotytech-negro.png'

interface PublicNavbarProps {
  theme: 'light' | 'dark'
  onToggleTheme: () => void
}

function handleInternalLink(event: React.MouseEvent<HTMLAnchorElement>, path: string): void {
  if (event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    navigate(path)
  }
}

export function PublicNavbar({ onToggleTheme, theme }: PublicNavbarProps): React.JSX.Element {
  const [menuOpen, setMenuOpen] = useState(false)

  useEffect(() => {
    if (!menuOpen) return

    const closeOnEscape = (event: KeyboardEvent): void => {
      if (event.key === 'Escape') setMenuOpen(false)
    }

    window.addEventListener('keydown', closeOnEscape)
    return () => window.removeEventListener('keydown', closeOnEscape)
  }, [menuOpen])

  const handleLink = (event: React.MouseEvent<HTMLAnchorElement>, path: string): void => {
    handleInternalLink(event, path)
    setMenuOpen(false)
  }

  const themeLabel = theme === 'dark' ? 'Usar tema claro' : 'Usar tema oscuro'

  return <header className="public-navbar"><div className="public-container public-navbar-row"><a aria-label="SotyTech propiedades" className="flex items-center gap-3" href="/propiedades" onClick={(event) => handleLink(event, '/propiedades')}><img alt="" aria-hidden="true" className="size-10 shrink-0 object-contain" src={brandLogoAsset} /><span><span className="block text-xl font-black tracking-[-0.05em] text-white">SotyTech</span><span className="block text-[10px] font-semibold tracking-[0.12em] text-white/60">BIENES RAÍCES</span></span></a><nav aria-label="Navegación pública" className="public-desktop-nav"><a href="/propiedades" onClick={(event) => handleLink(event, '/propiedades')}>Propiedades</a><a href="/explorar" onClick={(event) => handleLink(event, '/explorar')}>Explorar mapa</a><a href="/contacto" onClick={(event) => handleLink(event, '/contacto')}>Atención personalizada</a></nav><div className="flex items-center gap-2"><button aria-label={theme === 'dark' ? 'Activar tema claro' : 'Activar tema oscuro'} className="public-icon-button public-desktop-theme" onClick={onToggleTheme} type="button">{theme === 'dark' ? '☼' : '☾'}</button><button aria-controls="public-mobile-menu" aria-expanded={menuOpen} aria-label={menuOpen ? 'Cerrar menú' : 'Abrir menú'} className="public-icon-button public-menu-button" onClick={() => setMenuOpen((current) => !current)} type="button"><span aria-hidden="true">{menuOpen ? '×' : '☰'}</span></button></div></div><nav aria-label="Menú móvil" className={`public-mobile-menu${menuOpen ? ' public-mobile-menu-open' : ''}`} id="public-mobile-menu"><a href="/propiedades" onClick={(event) => handleLink(event, '/propiedades')}>Propiedades</a><a href="/explorar" onClick={(event) => handleLink(event, '/explorar')}>Explorar mapa</a><a href="/contacto" onClick={(event) => handleLink(event, '/contacto')}>Atención personalizada</a><button onClick={() => { onToggleTheme(); setMenuOpen(false) }} type="button"><span aria-hidden="true">{theme === 'dark' ? '☼' : '☾'}</span>{themeLabel}</button></nav></header>
}
