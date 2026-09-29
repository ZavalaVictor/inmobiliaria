interface BrandLogoProps {
  compact?: boolean
  compactIcon?: boolean
}

export function BrandLogo({ compact = false, compactIcon = false }: BrandLogoProps): React.JSX.Element {
  return (
    <div className={`flex items-center gap-3 ${compact ? 'text-xl' : 'text-2xl'}`}>
      <svg aria-hidden="true" className={`${compactIcon ? 'size-9' : 'size-10'} shrink-0 text-[#0F766E]`} fill="none" viewBox="0 0 40 40">
        <path d="M7 34h26M10 34V16l10-7 10 7v18M15 34V21h10v13M20 13v4" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" />
        <path d="M14 25h2M24 25h2M14 29h2M24 29h2" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" />
      </svg>
      <span className="font-bold tracking-[-0.04em] text-[#0B294D]">SotyTech</span>
    </div>
  )
}
