import { BrandLogo } from '../auth/BrandLogo.tsx'
import { getNavigationItems, type NavigationItem } from '../../config/navigation.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { navigate } from '../../router/navigation.ts'

interface SidebarProps {
  mobile?: boolean
  onNavigate?: () => void
}

function isActive(item: NavigationItem): boolean {
  return window.location.pathname === item.href || window.location.pathname.startsWith(`${item.href}/`)
}

export function Sidebar({ mobile = false, onNavigate }: SidebarProps): React.JSX.Element {
  const { can, user } = useAuth()
  const items = getNavigationItems(user, can)

  function handleNavigate(href: string): void {
    navigate(href)
    onNavigate?.()
  }

  return (
    <aside aria-label="Navegación principal" className={`${mobile ? 'flex h-full w-[min(84vw,320px)]' : 'hidden lg:flex lg:w-64'} app-sidebar shrink-0 flex-col`}>
      <div className="border-b border-white/10 px-6 py-6">
        <BrandLogo compact variant="light" />
      </div>
      <nav className="flex-1 space-y-2 px-4 py-6">
        <p className="px-3 pb-2 text-[11px] font-bold uppercase tracking-[0.16em] text-white/45">Navegación</p>
        {items.map((item) => {
          const Icon = item.icon
          const active = isActive(item)

          return (
            <button aria-current={active ? 'page' : undefined} className={`app-sidebar-link ${active ? 'app-sidebar-link-active' : ''}`} key={item.href} onClick={() => handleNavigate(item.href)} type="button">
              <Icon className="size-5 shrink-0" />
              <span>{item.label}</span>
            </button>
          )
        })}
      </nav>
      <div className="border-t border-white/10 px-6 py-5 text-xs leading-5 text-white/50">Gestión inmobiliaria SotyTech</div>
    </aside>
  )
}
