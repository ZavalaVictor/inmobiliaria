import type { ButtonHTMLAttributes, ReactNode } from 'react'

export const authInputLabelClass = 'block text-base font-semibold text-[#0B1E38] sm:text-[17px]'
export const authInputContainerClass = 'flex h-[50px] items-center gap-3 rounded-xl border bg-white px-4 transition focus-within:ring-4 focus-within:ring-[#0F766E]/10'
export const authInputClass = 'min-w-0 flex-1 border-0 bg-transparent py-0 text-base text-[#0B1E38] outline-none placeholder:text-[#8292A7] sm:text-[17px]'

interface AuthHeadingProps {
  title: ReactNode
  description?: ReactNode
  eyebrow?: ReactNode
}

export function AuthHeading({ title, description, eyebrow }: AuthHeadingProps): React.JSX.Element {
  return (
    <div className="space-y-3">
      {eyebrow ? <p className="text-sm font-semibold uppercase tracking-[0.12em] text-[#C33E3E]">{eyebrow}</p> : null}
      <h1 className="text-4xl font-bold leading-tight tracking-[-0.045em] text-[#0B1E38] sm:text-[40px]">{title}</h1>
      {description ? <p className="text-base text-[#71849C] sm:text-lg">{description}</p> : null}
    </div>
  )
}

interface AuthPrimaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  isLoading?: boolean
  loadingLabel?: string
}

export function AuthPrimaryButton({ children, isLoading = false, loadingLabel, ...props }: AuthPrimaryButtonProps): React.JSX.Element {
  return (
    <button className="flex h-[50px] w-full items-center justify-center gap-3 rounded-xl bg-[#0F8582] px-6 text-lg font-semibold text-white shadow-[0_8px_18px_rgba(15,118,110,0.18)] transition hover:bg-[#0B706D] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#0F766E]/25 disabled:cursor-not-allowed disabled:opacity-65" disabled={isLoading || props.disabled} {...props}>
      {isLoading ? <span aria-hidden="true" className="size-5 animate-spin rounded-full border-2 border-white/40 border-t-white" /> : null}
      {isLoading ? loadingLabel ?? children : children}
    </button>
  )
}

function ShieldIcon(): React.JSX.Element {
  return <svg aria-hidden="true" className="size-6" fill="none" viewBox="0 0 24 24"><path d="M12 3 19 6v5c0 4.6-2.9 8.2-7 10-4.1-1.8-7-5.4-7-10V6l7-3Z" stroke="currentColor" strokeLinejoin="round" strokeWidth="1.8" /><path d="m8.8 12 2.1 2.1 4.3-4.3" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" /></svg>
}

export function AuthSecurityFooter({ label = 'Acceso seguro a SotyTech', showShield = false }: { label?: string; showShield?: boolean }): React.JSX.Element {
  return (
    <div className="flex items-center gap-3 text-[#6A7D93]">
      <span className="h-px flex-1 bg-[#DCE5EB]" />
      <span className="flex items-center gap-2 text-sm font-medium">{showShield ? <ShieldIcon /> : null}{label}</span>
      <span className="h-px flex-1 bg-[#DCE5EB]" />
    </div>
  )
}
