import darkLogoAsset from '../../assets/branding/logo-sotytech-blanco.png'
import lightLogoAsset from '../../assets/branding/logo-sotytech-negro.png'

interface BrandLogoProps {
  compact?: boolean
  compactIcon?: boolean
  variant?: 'dark' | 'light'
}

export function BrandLogo({ compact = false, compactIcon = false, variant = 'dark' }: BrandLogoProps): React.JSX.Element {
  const logoAsset = variant === 'light' ? lightLogoAsset : darkLogoAsset

  return (
    <div className={`flex items-center gap-3 ${compact ? 'text-xl' : 'text-2xl'}`}>
      <img alt="" aria-hidden="true" className={`${compactIcon ? 'size-9' : 'size-10'} shrink-0 object-contain`} src={logoAsset} />
      <span className={`font-bold tracking-[-0.04em] ${variant === 'light' ? 'text-white' : 'text-[#0B294D]'}`}>SotyTech</span>
    </div>
  )
}
