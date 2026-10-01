import { useState } from 'react'
import { PropertyIcon } from '../app/NavIcons.tsx'
import type { InmuebleImagen } from '../../types/inmueble-imagenes.ts'

interface InmuebleImageThumbnailProps {
  image?: InmuebleImagen | null
  alt: string
  size?: 'sm' | 'md'
}

export function InmuebleImageThumbnail({ image, alt, size = 'sm' }: InmuebleImageThumbnailProps): React.JSX.Element {
  const [hasError, setHasError] = useState(false)
  const sizeClass = size === 'md' ? 'h-20 w-full' : 'h-14 w-20'

  if (image?.url_publica && !hasError) {
    return <img
      alt={image.texto_alternativo || alt}
      className={`${sizeClass} rounded-xl border border-[var(--app-border)] bg-[var(--app-surface-muted)] object-cover`}
      loading="lazy"
      onError={() => setHasError(true)}
      src={image.url_publica}
    />
  }

  return <div aria-label="Sin imagen principal" className={`${sizeClass} flex shrink-0 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-[var(--app-border)] bg-[var(--app-surface-muted)] px-2 text-center text-[var(--app-text-muted)]`} role="img">
    <PropertyIcon className="h-6 w-6" />
    <span className="text-[10px] font-semibold leading-tight">Sin imagen</span>
  </div>
}
