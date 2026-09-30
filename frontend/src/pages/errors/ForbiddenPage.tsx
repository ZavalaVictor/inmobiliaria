import { BrandLogo } from '../../components/auth/BrandLogo.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { getInitialRoute, navigate } from '../../router/navigation.ts'

export function ForbiddenPage(): React.JSX.Element {
  const { logout, user } = useAuth()

  return <main className="app-public-page grid min-h-screen place-items-center px-6 py-10"><section className="app-card w-full max-w-lg p-8 text-center sm:p-10"><BrandLogo compact /><p className="mt-10 text-6xl font-black tracking-[-0.06em] text-[var(--app-accent)]">403</p><h1 className="mt-4 text-2xl font-bold text-[var(--app-text)]">No tienes permisos para acceder a esta sección</h1><p className="mt-3 text-sm leading-6 text-[var(--app-text-muted)]">Regresa a tu espacio autorizado o cierra la sesión para cambiar de cuenta.</p><div className="mt-7 flex flex-col justify-center gap-3 sm:flex-row"><button className="app-button-primary" onClick={() => navigate(user ? getInitialRoute(user) : '/login')} type="button">Volver a mi inicio</button><button className="app-button-secondary" onClick={() => void logout()} type="button">Cerrar sesión</button></div></section></main>
}
