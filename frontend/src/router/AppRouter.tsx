import { useEffect, useState, type PropsWithChildren } from 'react'
import { ScreenLoader } from '../components/auth/ScreenLoader.tsx'
import { useAuth } from '../hooks/useAuth.ts'
import { LoginPage } from '../pages/auth/LoginPage.tsx'
import { NewPasswordPage } from '../pages/auth/NewPasswordPage.tsx'
import { PasswordRecoveryPage } from '../pages/auth/PasswordRecoveryPage.tsx'
import { PasswordUpdatedPage } from '../pages/auth/PasswordUpdatedPage.tsx'
import { ReviewEmailPage } from '../pages/auth/ReviewEmailPage.tsx'
import { PrivateAreaPage } from '../pages/private/PrivateAreaPage.tsx'
import { getInitialRoute, navigate } from './navigation.ts'

function usePathname(): string {
  const [pathname, setPathname] = useState(window.location.pathname)

  useEffect(() => {
    const handlePopState = (): void => setPathname(window.location.pathname)
    window.addEventListener('popstate', handlePopState)

    return () => window.removeEventListener('popstate', handlePopState)
  }, [])

  return pathname
}

function Redirect({ to }: { to: string }): React.JSX.Element {
  useEffect(() => navigate(to), [to])
  return <ScreenLoader />
}

function SessionUnavailable(): React.JSX.Element {
  return (
    <main className="grid min-h-screen place-items-center bg-[#F7FAFC] px-6">
      <section className="w-full max-w-lg rounded-2xl border border-[#DDE7ED] bg-white p-8 text-center shadow-[0_12px_35px_rgba(15,58,87,0.06)]">
        <p className="text-sm font-semibold uppercase tracking-[0.12em] text-[#0F766E]">Servicio temporalmente no disponible</p>
        <h1 className="mt-3 text-2xl font-bold text-[#0B1E38]">No pudimos verificar tu sesión</h1>
        <p className="mt-3 text-base leading-7 text-[#71849C]">El servidor no está disponible en este momento. Intenta nuevamente cuando esté en línea.</p>
        <button className="mt-6 rounded-xl bg-[#0F766E] px-5 py-3 font-semibold text-white transition hover:bg-[#0B5D5A] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#0F766E]/25" onClick={() => window.location.reload()} type="button">Reintentar</button>
      </section>
    </main>
  )
}

function ProtectedRoute({ children }: PropsWithChildren): React.JSX.Element {
  const { status } = useAuth()

  if (status === 'loading') {
    return <ScreenLoader />
  }

  if (status === 'unavailable') {
    return <SessionUnavailable />
  }

  if (status === 'unauthenticated') {
    return <Redirect to="/login" />
  }

  return <>{children}</>
}

function GuestRoute({ children }: PropsWithChildren): React.JSX.Element {
  const { status, user } = useAuth()

  if (status === 'loading') {
    return <ScreenLoader />
  }

  if (status === 'authenticated' && user) {
    return <Redirect to={getInitialRoute(user)} />
  }

  return <>{children}</>
}

export function AppRouter(): React.JSX.Element {
  const pathname = usePathname()
  const { clearNotice, status, user } = useAuth()

  useEffect(() => {
    if (pathname !== '/login') {
      clearNotice()
    }
  }, [clearNotice, pathname])

  if (pathname === '/') {
    if (status === 'loading') {
      return <ScreenLoader />
    }

    if (status === 'unavailable') {
      return <SessionUnavailable />
    }

    return <Redirect to={user ? getInitialRoute(user) : '/login'} />
  }

  if (pathname === '/login') {
    return <GuestRoute><LoginPage /></GuestRoute>
  }

  if (pathname === '/recuperar-contrasena') {
    return <PasswordRecoveryPage />
  }

  if (pathname === '/revisa-tu-correo') {
    return <ReviewEmailPage />
  }

  if (pathname === '/nueva-contrasena') {
    return <NewPasswordPage />
  }

  if (pathname === '/contrasena-actualizada') {
    return <PasswordUpdatedPage />
  }

  if (pathname === '/dashboard') {
    return <ProtectedRoute><PrivateAreaPage kind="admin" /></ProtectedRoute>
  }

  if (pathname === '/workspace') {
    return <ProtectedRoute><PrivateAreaPage kind="internal" /></ProtectedRoute>
  }

  if (pathname === '/portal-cliente') {
    return <ProtectedRoute><PrivateAreaPage kind="client" /></ProtectedRoute>
  }

  return <Redirect to={user ? getInitialRoute(user) : '/login'} />
}
