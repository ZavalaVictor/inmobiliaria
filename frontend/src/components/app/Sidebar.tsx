import { BrandLogo } from '../auth/BrandLogo.tsx'
import { getNavigationSections, isNavigationItemActive } from '../../config/navigation.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { getPrimaryRole, navigate } from '../../router/navigation.ts'

interface SidebarProps {
  mobile?: boolean
  onNavigate?: () => void
}

function getInitials(nombres: string, apellidos: string): string {
  const names = [nombres, ...apellidos.trim().split(/\s+/)].filter(Boolean)

  return names.slice(0, 2).map((name) => name.charAt(0).toUpperCase()).join('') || 'U'
}

export function Sidebar({ mobile = false, onNavigate }: SidebarProps): React.JSX.Element {
  const { can, user } = useAuth()
  const sections = getNavigationSections(user, can)

  function handleNavigate(event: React.MouseEvent<HTMLAnchorElement>, href: string): void {
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return
    }

    event.preventDefault()
    navigate(href)
    onNavigate?.()
  }

  return (
    <aside aria-label="Navegación principal" className={`${mobile ? 'flex h-full w-[min(84vw,320px)]' : 'hidden md:sticky md:top-0 md:flex md:h-screen md:max-h-screen md:w-64 md:overflow-hidden'} app-sidebar shrink-0 flex-col`}>
      <div className="app-sidebar-brand border-b border-white/10 px-5 py-6 lg:px-6">
        <div className="app-sidebar-brand-logo">
          <BrandLogo compact variant="light" />
          <p className="app-sidebar-brand-subtitle">Gestión Inmobiliaria</p>
        </div>
      </div>
      <div className="min-h-0 flex-1 overflow-y-auto">
        <nav aria-label="Secciones de la aplicación" className="space-y-5 px-3 py-5 lg:px-4 lg:py-6">
          {sections.map((section) => (
            <section key={section.label ?? 'sin-seccion'}>
              {section.label ? <h2 className="px-3 pb-2 text-[10px] font-semibold tracking-[0.14em] text-white/45">{section.label}</h2> : null}
              <div className="space-y-0.5">
                {section.items.map((item) => {
                  const Icon = item.icon
                  const active = isNavigationItemActive(item)

                  return <a aria-current={active ? 'page' : undefined} className={`app-sidebar-link ${active ? 'app-sidebar-link-active' : ''}`} href={item.href} key={item.href} onClick={(event) => handleNavigate(event, item.href)}><Icon className="size-[19px] shrink-0" /><span>{item.label}</span></a>
                })}
              </div>
            </section>
          ))}
        </nav>
      </div>
      {user ? <div className="border-t border-white/10 px-3 py-4 lg:px-4">
        <div className="flex items-center gap-3 rounded-xl px-2 py-2">
          <div aria-hidden="true" className="grid size-10 shrink-0 place-items-center rounded-full bg-[#e7eef8] text-xs font-bold text-[#142b49] shadow-sm">{getInitials(user.nombres, user.apellidos)}</div>
          <div className="min-w-0">
            <p className="truncate text-[13px] font-semibold text-white">{user.nombres} {user.apellidos}</p>
            <p className="truncate text-[11px] text-white/55">{getPrimaryRole(user.roles)}</p>
          </div>
        </div>
      </div> : null}
    </aside>
  )
}
