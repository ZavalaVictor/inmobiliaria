import { useEffect, useState, type PropsWithChildren } from 'react'
import { ScreenLoader } from '../components/auth/ScreenLoader.tsx'
import { useAuth } from '../hooks/useAuth.ts'
import { LoginPage } from '../pages/auth/LoginPage.tsx'
import { NewPasswordPage } from '../pages/auth/NewPasswordPage.tsx'
import { PasswordRecoveryPage } from '../pages/auth/PasswordRecoveryPage.tsx'
import { PasswordUpdatedPage } from '../pages/auth/PasswordUpdatedPage.tsx'
import { ReviewEmailPage } from '../pages/auth/ReviewEmailPage.tsx'
import { DashboardPage } from '../pages/dashboard/DashboardPage.tsx'
import { ForbiddenPage } from '../pages/errors/ForbiddenPage.tsx'
import { NotFoundPage } from '../pages/errors/NotFoundPage.tsx'
import { InmueblesPage } from '../pages/inmuebles/InmueblesPage.tsx'
import { InmuebleDetailPage } from '../pages/inmuebles/InmuebleDetailPage.tsx'
import { InmuebleFormPage } from '../pages/inmuebles/InmuebleFormPage.tsx'
import { PropietariosPage } from '../pages/propietarios/PropietariosPage.tsx'
import { ClienteDetailPage } from '../pages/clientes/ClienteDetailPage.tsx'
import { ClientesPage } from '../pages/clientes/ClientesPage.tsx'
import { SolicitudDetailPage } from '../pages/solicitudes/SolicitudDetailPage.tsx'
import { SolicitudesPage } from '../pages/solicitudes/SolicitudesPage.tsx'
import { PropiedadesPage } from '../pages/public/PropiedadesPage.tsx'
import { PortalPlaceholderPage } from '../pages/private/PortalPlaceholderPage.tsx'
import { WorkspacePage } from '../pages/private/WorkspacePage.tsx'
import { AppShell } from '../components/app/AppShell.tsx'
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

interface AccessRouteProps extends PropsWithChildren {
  permission?: string
  roles?: string[]
  exactRole?: string
}

function AccessRoute({ children, exactRole, permission, roles }: AccessRouteProps): React.JSX.Element {
  const { can, status, user } = useAuth()

  if (status === 'loading') {
    return <ScreenLoader />
  }

  if (status === 'unavailable') {
    return <SessionUnavailable />
  }

  if (status === 'unauthenticated' || !user) {
    return <Redirect to="/login" />
  }

  const roleAllowed = roles ? roles.some((role) => user.roles.includes(role)) : true
  const exactRoleAllowed = exactRole ? user.roles.length === 1 && user.roles[0] === exactRole : true
  const permissionAllowed = permission ? can(permission) : true

  if (!roleAllowed || !exactRoleAllowed || !permissionAllowed) {
    return <ForbiddenPage />
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

function InternalShell({ children }: PropsWithChildren): React.JSX.Element {
  return <AppShell>{children}</AppShell>
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

  if (pathname === '/propiedades') {
    return <PropiedadesPage />
  }

  if (pathname === '/dashboard') {
    return <AccessRoute permission="dashboard.ver" roles={['Administrador', 'Agente Inmobiliario', 'Director General']}><InternalShell><DashboardPage /></InternalShell></AccessRoute>
  }

  const propertyEditMatch = pathname.match(/^\/inmuebles\/(\d+)\/editar$/)
  if (propertyEditMatch) {
    return <AccessRoute permission="inmuebles.actualizar" roles={['Administrador', 'Agente Inmobiliario', 'Director General']}><InternalShell><InmuebleFormPage id={Number(propertyEditMatch[1])} /></InternalShell></AccessRoute>
  }

  if (pathname === '/inmuebles/nuevo') {
    return <AccessRoute permission="inmuebles.crear" roles={['Administrador', 'Director General']}><InternalShell><InmuebleFormPage /></InternalShell></AccessRoute>
  }

  const propertyDetailMatch = pathname.match(/^\/inmuebles\/(\d+)$/)
  if (propertyDetailMatch) {
    return <AccessRoute permission="inmuebles.ver" roles={['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']}><InternalShell><InmuebleDetailPage id={Number(propertyDetailMatch[1])} /></InternalShell></AccessRoute>
  }

  if (pathname === '/inmuebles') {
    return <AccessRoute permission="inmuebles.ver" roles={['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']}><InternalShell><InmueblesPage /></InternalShell></AccessRoute>
  }

  if (pathname === '/propietarios') {
    return <AccessRoute permission="propietarios.ver" roles={['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']}><InternalShell><PropietariosPage /></InternalShell></AccessRoute>
  }

  const clientDetailMatch = pathname.match(/^\/clientes\/(\d+)$/)
  if (clientDetailMatch) {
    return <AccessRoute permission="clientes.ver" roles={['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']}><InternalShell><ClienteDetailPage id={Number(clientDetailMatch[1])} key={clientDetailMatch[1]} /></InternalShell></AccessRoute>
  }

  if (pathname === '/clientes') {
    return <AccessRoute permission="clientes.ver" roles={['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']}><InternalShell><ClientesPage /></InternalShell></AccessRoute>
  }

  const requestDetailMatch = pathname.match(/^\/solicitudes\/(\d+)$/)
  if (requestDetailMatch) {
    return <AccessRoute permission="solicitudes.ver" roles={['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']}><InternalShell><SolicitudDetailPage id={Number(requestDetailMatch[1])} key={requestDetailMatch[1]} /></InternalShell></AccessRoute>
  }

  if (pathname === '/solicitudes') {
    return <AccessRoute permission="solicitudes.ver" roles={['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']}><InternalShell><SolicitudesPage /></InternalShell></AccessRoute>
  }

  if (pathname === '/workspace') {
    return <AccessRoute roles={['Administrador', 'Agente Inmobiliario', 'Asistente', 'Director General']}><InternalShell><WorkspacePage /></InternalShell></AccessRoute>
  }

  if (pathname === '/portal-cliente') {
    return <AccessRoute exactRole="Cliente" permission="portal_cliente.ver" roles={['Cliente']}><PortalPlaceholderPage /></AccessRoute>
  }

  if (status === 'loading') {
    return <ScreenLoader />
  }

  if (status === 'unavailable') {
    return <SessionUnavailable />
  }

  return <NotFoundPage />
}
