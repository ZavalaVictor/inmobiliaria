import { adminUrl, navigate } from '../navigation.ts'
import brandLogoAsset from '../../../frontend/src/assets/branding/logo-sotytech-negro.png'

interface PublicNavbarProps {
  theme: 'light' | 'dark'
  onToggleTheme: () => void
}

function handleHome(event: React.MouseEvent<HTMLAnchorElement>): void {
  if (event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    navigate('/propiedades')
  }
}

function handleContact(event: React.MouseEvent<HTMLAnchorElement>): void {
  if (event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    navigate('/contacto')
  }
}

function handleExplore(event: React.MouseEvent<HTMLAnchorElement>): void {
  if (event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    navigate('/explorar')
  }
}

export function PublicNavbar({ onToggleTheme, theme }: PublicNavbarProps): React.JSX.Element {
  return <header className="public-navbar"><div className="public-container flex items-center justify-between gap-5 py-4"><a aria-label="SotyTech propiedades" className="flex items-center gap-3" href="/propiedades" onClick={handleHome}><img alt="" aria-hidden="true" className="size-10 shrink-0 object-contain" src={brandLogoAsset} /><span><span className="block text-xl font-black tracking-[-0.05em] text-white">SotyTech</span><span className="block text-[10px] font-semibold tracking-[0.12em] text-white/60">BIENES RAÍCES</span></span></a><nav aria-label="Navegación pública" className="hidden items-center gap-6 text-sm font-semibold text-white/75 md:flex"><a className="hover:text-white" href="/propiedades" onClick={handleHome}>Propiedades</a><a className="hover:text-white" href="/explorar" onClick={handleExplore}>Explorar mapa</a><a className="hover:text-white" href="/contacto" onClick={handleContact}>Atención personalizada</a></nav><div className="flex items-center gap-2"><button aria-label={theme === 'dark' ? 'Activar tema claro' : 'Activar tema oscuro'} className="public-icon-button" onClick={onToggleTheme} type="button">{theme === 'dark' ? '☼' : '☾'}</button><a className="public-navbar-button" href={adminUrl('/login')}>Portal administrativo</a></div></div></header>
}
