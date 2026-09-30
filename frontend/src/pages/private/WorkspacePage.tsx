import { AppShell } from '../../components/app/AppShell.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { getPrimaryRole } from '../../router/navigation.ts'

export function WorkspacePage(): React.JSX.Element {
  const { user } = useAuth()

  return <AppShell>
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="app-card p-6 sm:p-8"><p className="text-sm font-semibold uppercase tracking-[0.12em] text-[var(--app-accent)]">Área de trabajo</p><h2 className="mt-3 text-3xl font-bold tracking-[-0.04em] text-[var(--app-text)]">Hola, {user?.nombres ?? 'bienvenido'}</h2><p className="mt-3 max-w-2xl text-base leading-7 text-[var(--app-text-muted)]">Tu espacio operativo está listo. Las herramientas disponibles se incorporarán gradualmente según tus permisos.</p><div className="mt-6 inline-flex rounded-full bg-[var(--app-accent-soft)] px-3 py-1.5 text-sm font-semibold text-[var(--app-accent)]">{getPrimaryRole(user?.roles ?? [])}</div></div>
      <div className="app-card p-6"><h3 className="text-lg font-bold text-[var(--app-text)]">Próximamente</h3><p className="mt-2 text-sm leading-6 text-[var(--app-text-muted)]">Desde aquí podrás consultar y gestionar las tareas operativas permitidas para tu perfil.</p></div>
    </div>
  </AppShell>
}
