import { BrandLogo } from '../../components/auth/BrandLogo.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { navigate } from '../../router/navigation.ts'

export function PortalPlaceholderPage(): React.JSX.Element {
  const { logout, user } = useAuth()

  return <main className="app-public-page min-h-screen px-4 py-6 sm:px-8"><div className="mx-auto flex min-h-[calc(100vh-3rem)] w-full max-w-5xl flex-col"><header className="flex items-center justify-between gap-4"><BrandLogo compact /><button className="app-button-secondary" onClick={() => void logout()} type="button">Cerrar sesión</button></header><section className="grid flex-1 place-items-center py-16"><div className="app-card w-full max-w-2xl p-8 text-center sm:p-12"><span className="inline-flex rounded-full bg-[var(--app-accent-soft)] px-3 py-1.5 text-sm font-semibold text-[var(--app-accent)]">Portal Cliente</span><h1 className="mt-5 text-3xl font-bold tracking-[-0.04em] text-[var(--app-text)]">Hola, {user?.nombres ?? 'bienvenido'}</h1><p className="mt-4 text-base leading-7 text-[var(--app-text-muted)]">Tu portal está preparado. En la siguiente fase podrás consultar tus inmuebles de interés, citas y notificaciones.</p><button className="app-button-primary mt-7" onClick={() => navigate('/login')} type="button">Volver al inicio</button></div></section></div></main>
}
