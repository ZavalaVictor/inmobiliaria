import { useState } from 'react'
import { BrandLogo } from '../../components/auth/BrandLogo.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import type { UserRole } from '../../types/auth.ts'

function getRoleLabel(roles: string[]): string {
  return roles.find((role): role is UserRole => [
    'Administrador',
    'Agente Inmobiliario',
    'Asistente',
    'Director General',
    'Cliente',
  ].includes(role)) ?? 'Usuario autenticado'
}

export function PrivateAreaPage({ kind }: { kind: 'admin' | 'internal' | 'client' }): React.JSX.Element {
  const { logout, user } = useAuth()
  const [isLoggingOut, setIsLoggingOut] = useState(false)
  const isClient = kind === 'client'
  const title = kind === 'admin' ? 'Dashboard administrativo' : isClient ? 'Portal Cliente' : 'Área interna'
  const description = kind === 'admin'
    ? 'Sesión autenticada correctamente. Esta shell prepara la entrada para el dashboard administrativo.'
    : isClient
      ? 'Sesión autenticada correctamente. El portal Cliente se habilitará en una fase posterior.'
      : 'Sesión autenticada correctamente. El área autorizada para este perfil se habilitará en una fase posterior.'

  async function handleLogout(): Promise<void> {
    setIsLoggingOut(true)
    try {
      await logout()
    } finally {
      setIsLoggingOut(false)
    }
  }

  return (
    <main className="min-h-screen bg-[#F7FAFC] text-[#0B1E38]">
      <header className="border-b border-[#DDE7ED] bg-white px-6 py-5 sm:px-10">
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-5">
          <BrandLogo compact />
          <div className="flex items-center gap-4">
            <div className="hidden text-right sm:block">
              <p className="font-semibold">{user?.nombres} {user?.apellidos}</p>
              <p className="text-sm text-[#71849C]">{getRoleLabel(user?.roles ?? [])}</p>
            </div>
            <button className="rounded-lg border border-[#C7D5E0] px-4 py-2 text-sm font-semibold text-[#34516C] transition hover:border-[#0F766E] hover:text-[#0F766E] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#0F766E]/15 disabled:opacity-60" disabled={isLoggingOut} onClick={handleLogout} type="button">{isLoggingOut ? 'Cerrando...' : 'Cerrar sesión'}</button>
          </div>
        </div>
      </header>
      <section className="mx-auto max-w-7xl px-6 py-12 sm:px-10">
        <div className="max-w-2xl rounded-2xl border border-[#DDE7ED] bg-white p-8 shadow-[0_12px_35px_rgba(15,58,87,0.06)]">
          <span className="inline-flex rounded-full bg-[#E6F5F4] px-3 py-1 text-sm font-semibold text-[#0F766E]">Acceso protegido</span>
          <h1 className="mt-5 text-3xl font-bold tracking-[-0.035em]">{title}</h1>
          <p className="mt-3 text-lg leading-8 text-[#71849C]">{description}</p>
          <p className="mt-6 border-t border-[#E8EEF2] pt-5 text-sm leading-6 text-[#7890A8]">Las capacidades visibles se incorporarán por fases. La autorización real continúa siendo responsabilidad del backend.</p>
        </div>
      </section>
    </main>
  )
}
