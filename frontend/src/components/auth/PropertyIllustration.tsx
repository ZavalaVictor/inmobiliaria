import darkIllustrationAsset from '../../assets/branding/property-illustration-dark.png'
import lightIllustrationAsset from '../../assets/branding/property-illustration-light.png'

interface PropertyIllustrationProps {
  variant?: 'dark' | 'light'
}

export function PropertyIllustration({ variant = 'light' }: PropertyIllustrationProps): React.JSX.Element {
  const illustrationAsset = variant === 'dark' ? darkIllustrationAsset : lightIllustrationAsset

  return (
    <div className="aspect-[520/330] w-full">
      <img alt="" aria-hidden="true" className="block size-full object-contain" src={illustrationAsset} />
    </div>
  )
}
