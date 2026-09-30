import { BrandLogo } from '../../components/auth/BrandLogo.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { getInitialRoute, navigate } from '../../router/navigation.ts'

export function NotFoundPage(): React.JSX.Element {
  const { user } = useAuth()

  return <main className="app-public-page grid min-h-screen place-items-center px-6 py-10"><section className="app-card w-full max-w-lg p-8 text-center sm:p-10"><BrandLogo compact /><p className="mt-10 text-6xl font-black tracking-[-0.06em] text-[var(--app-navy)]">404</p><h1 className="mt-4 text-2xl font-bold text-[var(--app-text)]">No encontramos esta página</h1><p className="mt-3 text-sm leading-6 text-[var(--app-text-muted)]">La dirección puede haber cambiado o no estar disponible para tu cuenta.</p><button className="app-button-primary mt-7" onClick={() => navigate(user ? getInitialRoute(user) : '/login')} type="button">Continuar</button></section></main>
}
