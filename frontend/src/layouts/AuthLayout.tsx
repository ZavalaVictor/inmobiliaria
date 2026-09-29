import type { PropsWithChildren } from 'react'
import { BrandLogo } from '../components/auth/BrandLogo.tsx'
import { PropertyIllustration } from '../components/auth/PropertyIllustration.tsx'

interface AuthLayoutProps extends PropsWithChildren {
  panelTitle?: string
  panelDescription?: string
}

export function AuthLayout({ children, panelTitle = 'Gestión inmobiliaria simple y centralizada.', panelDescription = 'Clientes, inmuebles, oportunidades y operaciones en un solo lugar.' }: AuthLayoutProps): React.JSX.Element {
  return (
    <main className="min-h-screen bg-[#F7FAFC] px-4 py-4 sm:px-8 sm:py-8 lg:flex lg:items-center lg:justify-center lg:px-6 lg:py-6">
      <div className="mx-auto flex w-full max-w-[1160px] overflow-hidden rounded-2xl border border-[#DDE7ED] bg-white shadow-[0_18px_55px_rgba(15,58,87,0.08)] lg:min-h-[min(660px,calc(100vh-3rem))]">
        <aside className="relative hidden w-[42%] max-w-[570px] flex-col justify-between bg-[#F7FBFD] px-8 py-9 lg:flex xl:px-10">
          <BrandLogo compact compactIcon />
          <div className="space-y-4">
            <div className="mx-auto w-[90%]"><PropertyIllustration /></div>
            <div className="max-w-[350px] space-y-2">
              <h2 className="text-[23px] font-bold leading-[1.15] tracking-[-0.03em] text-[#0B294D]">{panelTitle}</h2>
              <p className="text-[16px] leading-6 text-[#7890A8]">{panelDescription}</p>
            </div>
          </div>
          <span className="text-xs font-medium uppercase tracking-[0.16em] text-[#A5B8C5]">Plataforma inmobiliaria</span>
        </aside>
        <section className="flex min-w-0 flex-1 items-center justify-center px-5 py-8 sm:px-8 lg:px-12 xl:px-16">
          <div className="w-full max-w-[520px]">
            <div className="mb-8 lg:hidden"><BrandLogo compact /></div>
            {children}
          </div>
        </section>
      </div>
    </main>
  )
}
