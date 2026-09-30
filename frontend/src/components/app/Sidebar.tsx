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
    <aside aria-label="Navegación principal" className={`${mobile ? 'flex h-full w-[min(84vw,320px)]' : 'hidden lg:flex lg:min-h-screen lg:w-64'} app-sidebar shrink-0 flex-col`}>
      <div className="border-b border-white/10 px-6 py-6">
        <BrandLogo compact variant="light" />
        <p className="mt-2 text-[11px] font-medium tracking-[0.08em] text-white/50">Gestión inmobiliaria</p>
      </div>
      <div className="min-h-0 flex-1 overflow-y-auto">
        <nav aria-label="Secciones de la aplicación" className="space-y-6 px-4 py-6">
          {sections.map((section) => (
            <section key={section.label ?? 'sin-seccion'}>
              {section.label ? <h2 className="px-3 pb-2 text-[10px] font-bold tracking-[0.18em] text-white/45">{section.label}</h2> : null}
              <div className="space-y-1">
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
      {user ? <div className="border-t border-white/10 px-4 py-4">
        <div className="flex items-center gap-3 rounded-xl px-2 py-2">
          <div aria-hidden="true" className="grid size-9 shrink-0 place-items-center rounded-full bg-[var(--app-accent)] text-xs font-bold text-white">{getInitials(user.nombres, user.apellidos)}</div>
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-white">{user.nombres} {user.apellidos}</p>
            <p className="truncate text-xs text-white/55">{getPrimaryRole(user.roles)}</p>
          </div>
        </div>
      </div> : null}
    </aside>
  )
}
