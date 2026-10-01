import { useEffect, useMemo, useRef, useState } from 'react'
import { useAuth } from '../../hooks/useAuth.ts'
import {
  deleteInmuebleImagen,
  listInmuebleImagenes,
  reorderInmuebleImagenes,
  setInmuebleImagenPrincipal,
  updateInmuebleImagen,
  uploadInmuebleImagen,
} from '../../services/inmueble-imagenes.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'
import type { InmuebleImagen } from '../../types/inmueble-imagenes.ts'
import { EmptyState } from '../ui/EmptyState.tsx'
import { ErrorState } from '../ui/ErrorState.tsx'
import { LoadingState } from '../ui/LoadingState.tsx'

function sortImages(images: InmuebleImagen[]): InmuebleImagen[] {
  return [...images].sort((left, right) => {
    if (left.es_principal !== right.es_principal) return left.es_principal ? -1 : 1
    if (left.orden !== right.orden) return left.orden - right.orden
    return left.id - right.id
  })
}

function formatBytes(bytes: number | null): string {
  if (bytes === null) return 'Tamaño no disponible'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function isImageUploadError(error: unknown): boolean {
  return error instanceof ApiError && error.status === 422
}

function imageUploadErrorMessage(error: unknown): string {
  if (!(error instanceof ApiError)) return 'No pudimos completar la operación de imágenes.'

  const messages = getValidationErrors(error.payload).imagen ?? []
  if (messages.some((message) => /failed to upload|upload/i.test(message))) {
    return 'El servidor no recibió la imagen completa. Reinicia el backend con un límite de subida de al menos 10 MB.'
  }

  return 'La imagen no cumple con el formato, tamaño o datos permitidos.'
}

export function InmuebleGallery({ inmuebleId }: { inmuebleId: number }): React.JSX.Element | null {
  const { can } = useAuth()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [images, setImages] = useState<InmuebleImagen[]>([])
  const [activeImageId, setActiveImageId] = useState<number | null>(null)
  const [loadError, setLoadError] = useState<unknown>(null)
  const [loadedRequestKey, setLoadedRequestKey] = useState<string | null>(null)
  const [reloadKey, setReloadKey] = useState(0)
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [fileError, setFileError] = useState<string | null>(null)
  const [uploadAlt, setUploadAlt] = useState('')
  const [isUploading, setIsUploading] = useState(false)
  const [editingImageId, setEditingImageId] = useState<number | null>(null)
  const [altDraft, setAltDraft] = useState('')
  const [isMutating, setIsMutating] = useState(false)
  const [mutationError, setMutationError] = useState<unknown>(null)

  const canView = can('imagenes_inmuebles.ver')
  const canCreate = can('imagenes_inmuebles.crear')
  const canUpdate = can('imagenes_inmuebles.actualizar')
  const canDelete = can('imagenes_inmuebles.eliminar')
  const requestKey = `${inmuebleId}-${reloadKey}`

  useEffect(() => {
    if (!canView) return

    const controller = new AbortController()

    listInmuebleImagenes(inmuebleId, controller.signal)
      .then((loadedImages) => {
        const orderedImages = sortImages(loadedImages)
        setImages(orderedImages)
        setActiveImageId((current) => current && orderedImages.some((image) => image.id === current)
          ? current
          : orderedImages[0]?.id ?? null)
        setLoadError(null)
        setLoadedRequestKey(requestKey)
      })
      .catch((nextError: unknown) => {
        if (!controller.signal.aborted) {
          setLoadError(nextError)
          setLoadedRequestKey(requestKey)
        }
      })

    return () => controller.abort()
  }, [canView, inmuebleId, requestKey])

  const activeImage = useMemo(
    () => images.find((image) => image.id === activeImageId) ?? images[0] ?? null,
    [activeImageId, images],
  )

  if (!canView) return null

  if (loadedRequestKey === requestKey && loadError instanceof ApiError && loadError.status === 403) return null
  if (loadedRequestKey === requestKey && loadError) {
    return <section className="app-card p-5 sm:p-6"><ErrorState message="No pudimos cargar la galería del inmueble." onRetry={() => { setLoadError(null); setLoadedRequestKey(null); setReloadKey((current) => current + 1) }} /></section>
  }

  const handleUpload = async (): Promise<void> => {
    if (!selectedFile) return
    setIsUploading(true)
    setMutationError(null)
    try {
      const uploaded = await uploadInmuebleImagen(inmuebleId, selectedFile, uploadAlt.trim() || undefined)
      setImages((current) => sortImages([...current, uploaded]))
      setActiveImageId(uploaded.id)
      setSelectedFile(null)
      setUploadAlt('')
      if (fileInputRef.current) fileInputRef.current.value = ''
    } catch (nextError: unknown) {
      setMutationError(nextError)
    } finally {
      setIsUploading(false)
    }
  }

  const handleFileChange = (file: File | null): void => {
    setFileError(null)
    setSelectedFile(null)
    if (!file) return

    const acceptedTypes = ['image/jpeg', 'image/png', 'image/webp']
    if (!acceptedTypes.includes(file.type)) {
      setFileError('Selecciona una imagen JPG, PNG o WEBP.')
      return
    }

    if (file.size > 10 * 1024 * 1024) {
      setFileError('La imagen supera el máximo permitido de 10 MB.')
      return
    }

    setSelectedFile(file)
  }

  const handleSetPrincipal = async (imageId: number): Promise<void> => {
    setIsMutating(true)
    setMutationError(null)
    try {
      await setInmuebleImagenPrincipal(inmuebleId, imageId)
      setImages((current) => sortImages(current.map((image) => ({ ...image, es_principal: image.id === imageId }))))
      setActiveImageId(imageId)
    } catch (nextError: unknown) {
      setMutationError(nextError)
    } finally {
      setIsMutating(false)
    }
  }

  const handleSaveAlt = async (image: InmuebleImagen): Promise<void> => {
    setIsMutating(true)
    setMutationError(null)
    try {
      const updated = await updateInmuebleImagen(inmuebleId, image.id, { texto_alternativo: altDraft.trim() || null })
      setImages((current) => current.map((item) => item.id === updated.id ? updated : item))
      setEditingImageId(null)
    } catch (nextError: unknown) {
      setMutationError(nextError)
    } finally {
      setIsMutating(false)
    }
  }

  const handleDelete = async (image: InmuebleImagen): Promise<void> => {
    if (!window.confirm(`¿Eliminar la imagen ${image.nombre_original ?? ''}?`)) return
    setIsMutating(true)
    setMutationError(null)
    try {
      await deleteInmuebleImagen(inmuebleId, image.id)
      setImages((current) => current.filter((item) => item.id !== image.id))
      setActiveImageId((current) => current === image.id ? null : current)
    } catch (nextError: unknown) {
      setMutationError(nextError)
    } finally {
      setIsMutating(false)
    }
  }

  const handleMove = async (imageId: number, direction: -1 | 1): Promise<void> => {
    const currentIndex = images.findIndex((image) => image.id === imageId)
    const nextIndex = currentIndex + direction
    if (currentIndex < 0 || nextIndex < 0 || nextIndex >= images.length) return

    const reordered = [...images]
    const [movedImage] = reordered.splice(currentIndex, 1)
    reordered.splice(nextIndex, 0, movedImage)
    setIsMutating(true)
    setMutationError(null)
    try {
      const updated = await reorderInmuebleImagenes(inmuebleId, { imagenes: reordered.map((image) => image.id) })
      setImages(sortImages(updated))
    } catch (nextError: unknown) {
      setMutationError(nextError)
    } finally {
      setIsMutating(false)
    }
  }

  if (loadedRequestKey !== requestKey) return <section className="app-card p-5 sm:p-6"><LoadingState compact label="Cargando galería..." /></section>

  return <section className="app-card p-5 sm:p-6">
    <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
      <div><h2 className="text-lg font-bold text-[var(--app-text)]">Galería de imágenes</h2><p className="mt-1 text-sm text-[var(--app-text-muted)]">Administra las imágenes públicas de este inmueble.</p></div>
      <p className="text-sm font-semibold text-[var(--app-text-muted)]">{images.length} {images.length === 1 ? 'imagen' : 'imágenes'}</p>
    </div>

    {fileError ? <p aria-live="polite" className="mt-4 rounded-xl border border-[var(--app-danger-border)] bg-[var(--app-danger-surface)] p-3 text-sm font-semibold text-[var(--app-danger)]">{fileError}</p> : null}
    {mutationError ? <p aria-live="polite" className="mt-4 rounded-xl border border-[var(--app-danger-border)] bg-[var(--app-danger-surface)] p-3 text-sm font-semibold text-[var(--app-danger)]">{isImageUploadError(mutationError) ? imageUploadErrorMessage(mutationError) : 'No pudimos completar la operación de imágenes.'}</p> : null}

    {images.length === 0 ? <div className="mt-5"><EmptyState title="Este inmueble aún no tiene imágenes" message={canCreate ? 'Sube una imagen JPG, PNG o WEBP de hasta 10 MB.' : 'Las imágenes aparecerán aquí cuando estén disponibles.'} action={canCreate ? { label: 'Seleccionar imagen', onClick: () => fileInputRef.current?.click() } : undefined} /></div> : <div className="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
      <div className="overflow-hidden rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-muted)]">
        <div className="flex min-h-72 items-center justify-center p-3 sm:min-h-96">
          {activeImage?.url_publica ? <img alt={activeImage.texto_alternativo ?? activeImage.nombre_original ?? 'Imagen del inmueble'} className="max-h-[28rem] w-full rounded-xl object-contain" src={activeImage.url_publica} /> : <div className="text-center text-[var(--app-text-muted)]"><span aria-hidden="true" className="text-5xl">▧</span><p className="mt-2 text-sm">Vista previa no disponible</p></div>}
        </div>
        {activeImage ? <div className="border-t border-[var(--app-border)] bg-[var(--app-surface)] p-4"><div className="flex flex-wrap items-center justify-between gap-3"><div className="min-w-0"><p className="truncate text-sm font-bold text-[var(--app-text)]">{activeImage.nombre_original ?? 'Imagen sin nombre'}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{formatBytes(activeImage.tamano_bytes)}{activeImage.es_principal ? ' · Imagen principal' : ''}</p></div>{!activeImage.es_principal && canUpdate ? <button className="app-button-secondary text-xs" disabled={isMutating} onClick={() => void handleSetPrincipal(activeImage.id)} type="button">Usar como principal</button> : null}</div></div> : null}
      </div>
      <div className="grid content-start grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-2">
        {images.map((image, index) => <div className={`group relative overflow-hidden rounded-xl border ${image.id === activeImage?.id ? 'border-[var(--app-accent)] ring-2 ring-[var(--app-accent)]/20' : 'border-[var(--app-border)]'} bg-[var(--app-surface-muted)]`} key={image.id}>
          <button aria-label={`Ver ${image.nombre_original ?? 'imagen'}`} className="block aspect-[4/3] w-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[var(--app-accent)]" onClick={() => setActiveImageId(image.id)} type="button">{image.url_publica ? <img alt="" className="size-full object-cover" src={image.url_publica} /> : <span aria-hidden="true" className="grid size-full place-items-center text-3xl text-[var(--app-text-muted)]">▧</span>}</button>
          <div className="border-t border-[var(--app-border)] bg-[var(--app-surface)] p-2"><div className="flex items-center justify-between gap-2"><span className="truncate text-[11px] font-semibold text-[var(--app-text-muted)]">{image.es_principal ? 'Principal' : `Imagen ${index + 1}`}</span>{canDelete ? <button aria-label={`Eliminar ${image.nombre_original ?? 'imagen'}`} className="text-xs font-bold text-[var(--app-danger)] hover:underline" disabled={isMutating} onClick={() => void handleDelete(image)} type="button">Eliminar</button> : null}</div>{canUpdate ? <div className="mt-2 flex items-center justify-between gap-1"><button aria-label="Mover imagen a la izquierda" className="rounded px-1.5 py-1 text-xs text-[var(--app-text-muted)] hover:bg-[var(--app-surface-muted)] disabled:opacity-40" disabled={isMutating || index === 0} onClick={() => void handleMove(image.id, -1)} type="button">←</button><button aria-label="Editar texto alternativo" className="truncate px-1 text-[11px] font-semibold text-[var(--app-accent)] hover:underline" onClick={() => { setEditingImageId(image.id); setAltDraft(image.texto_alternativo ?? '') }} type="button">Texto alt</button><button aria-label="Mover imagen a la derecha" className="rounded px-1.5 py-1 text-xs text-[var(--app-text-muted)] hover:bg-[var(--app-surface-muted)] disabled:opacity-40" disabled={isMutating || index === images.length - 1} onClick={() => void handleMove(image.id, 1)} type="button">→</button></div> : null}</div>
          {editingImageId === image.id ? <div className="border-t border-[var(--app-border)] p-2"><label className="text-[11px] font-semibold text-[var(--app-text-muted)]" htmlFor={`alt-${image.id}`}>Texto alternativo</label><input className="app-input mt-1 text-xs" id={`alt-${image.id}`} maxLength={180} onChange={(event) => setAltDraft(event.target.value)} value={altDraft} /><div className="mt-2 flex justify-end gap-2"><button className="text-xs font-semibold text-[var(--app-text-muted)]" onClick={() => setEditingImageId(null)} type="button">Cancelar</button><button className="text-xs font-bold text-[var(--app-accent)] disabled:opacity-50" disabled={isMutating} onClick={() => void handleSaveAlt(image)} type="button">Guardar</button></div></div> : null}
        </div>)}
      </div>
    </div>}

    {canCreate ? <div className="mt-5 rounded-xl border border-dashed border-[var(--app-border)] bg-[var(--app-surface-muted)] p-4"><div className="flex flex-col gap-3 lg:flex-row lg:items-end"><div className="min-w-0 flex-1"><label className="text-sm font-semibold text-[var(--app-text)]" htmlFor={`gallery-file-${inmuebleId}`}>Agregar imagen</label><input accept="image/jpeg,image/png,image/webp" className="app-input mt-2 w-full text-sm" id={`gallery-file-${inmuebleId}`} onChange={(event) => handleFileChange(event.target.files?.[0] ?? null)} ref={fileInputRef} type="file" /><p className="mt-1 text-xs text-[var(--app-text-muted)]">JPG, PNG o WEBP · máximo 10 MB.</p></div><div className="min-w-0 flex-1"><label className="text-sm font-semibold text-[var(--app-text)]" htmlFor={`gallery-alt-${inmuebleId}`}>Texto alternativo <span className="font-normal text-[var(--app-text-muted)]">(opcional)</span></label><input className="app-input mt-2 w-full text-sm" id={`gallery-alt-${inmuebleId}`} maxLength={180} onChange={(event) => setUploadAlt(event.target.value)} placeholder="Describe brevemente la imagen" value={uploadAlt} /></div><button className="app-button-primary shrink-0" disabled={!selectedFile || isUploading} onClick={() => void handleUpload()} type="button">{isUploading ? 'Subiendo…' : 'Subir imagen'}</button></div></div> : null}
  </section>
}
