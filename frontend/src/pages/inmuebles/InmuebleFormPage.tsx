import { useEffect, useState } from 'react'
import { InmuebleForm } from '../../components/inmuebles/InmuebleForm.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { listCategorias } from '../../services/categorias.ts'
import { createInmueble, getInmueble, updateInmueble } from '../../services/inmuebles.ts'
import { listPropietarios } from '../../services/propietarios.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'
import { useAuth } from '../../hooks/useAuth.ts'
import { navigate } from '../../router/navigation.ts'
import type { CategoriaOption } from '../../types/categorias.ts'
import type { InmuebleFormValues, InmuebleWritePayload } from '../../types/inmuebles.ts'
import type { PropietarioOption } from '../../types/propietarios.ts'
import { emptyInmuebleForm, inmuebleToFormValues } from '../../utils/inmuebleForm.ts'
import { ForbiddenPage } from '../errors/ForbiddenPage.tsx'
import { NotFoundPage } from '../errors/NotFoundPage.tsx'

interface InmuebleFormPageProps {
  id?: number
}

export function InmuebleFormPage({ id }: InmuebleFormPageProps): React.JSX.Element {
  const { user } = useAuth()
  const isEdit = id !== undefined
  const [property, setProperty] = useState<InmuebleFormValues | null>(isEdit ? null : emptyInmuebleForm)
  const [categories, setCategories] = useState<CategoriaOption[] | null>(null)
  const [owners, setOwners] = useState<PropietarioOption[] | null>(null)
  const [isLoading, setIsLoading] = useState(isEdit)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [loadError, setLoadError] = useState<unknown>(null)
  const [submitError, setSubmitError] = useState<unknown>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})

  useEffect(() => {
    const controller = new AbortController()
    Promise.allSettled([listCategorias(controller.signal), listPropietarios(controller.signal)]).then(([categoryResult, ownerResult]) => {
      if (categoryResult.status === 'fulfilled') setCategories(categoryResult.value.data)
      if (ownerResult.status === 'fulfilled') setOwners(ownerResult.value.data)
    })
    if (id === undefined) return () => controller.abort()
    getInmueble(id, controller.signal)
      .then((loaded) => setProperty(inmuebleToFormValues(loaded)))
      .catch((nextError: unknown) => {
        if (!controller.signal.aborted) setLoadError(nextError)
      })
      .finally(() => { if (!controller.signal.aborted) setIsLoading(false) })
    return () => controller.abort()
  }, [id])

  if (loadError instanceof ApiError && loadError.status === 403) return <ForbiddenPage />
  if (loadError instanceof ApiError && loadError.status === 404) return <NotFoundPage />
  if (loadError) return <ErrorState message="No pudimos cargar el inmueble. Intenta nuevamente." onRetry={() => window.location.reload()} />
  if (isLoading || !property) return <LoadingState label={isEdit ? 'Cargando inmueble...' : 'Preparando formulario...'} />

  const canChangeRelations = !isEdit || !user?.roles.includes('Agente Inmobiliario')
  const submit = async (payload: InmuebleWritePayload): Promise<void> => {
    setIsSubmitting(true)
    setSubmitError(null)
    setFieldErrors({})
    try {
      const saved = isEdit && id !== undefined ? await updateInmueble(id, payload) : await createInmueble(payload)
      navigate(`/inmuebles/${saved.id}`)
    } catch (nextError: unknown) {
      setSubmitError(nextError)
      if (nextError instanceof ApiError && nextError.status === 422) setFieldErrors(getValidationErrors(nextError.payload))
    } finally {
      setIsSubmitting(false)
    }
  }

  return <div className="space-y-6">
    <div><nav aria-label="Migas de pan" className="flex items-center gap-2 text-xs font-semibold text-[var(--app-text-muted)]"><a className="hover:text-[var(--app-accent)]" href="/inmuebles">Inmuebles</a><span aria-hidden="true">›</span><span className="text-[var(--app-accent)]">{isEdit ? 'Editar' : 'Nuevo inmueble'}</span></nav><h1 className="mt-2 text-2xl font-bold tracking-[-0.035em] text-[var(--app-text)] sm:text-3xl">{isEdit ? 'Editar inmueble' : 'Nuevo inmueble'}</h1><p className="mt-2 text-sm text-[var(--app-text-muted)]">Completa la información real de la propiedad.</p></div>
    {submitError ? <ErrorState message={submitError instanceof ApiError && submitError.status === 422 ? 'Revisa los campos marcados y corrige la información.' : 'No pudimos guardar el inmueble. Intenta nuevamente.'} title="No se pudo guardar" /> : null}
    <InmuebleForm canChangeRelations={canChangeRelations} categories={categories} fieldErrors={fieldErrors} initialValues={property} isSubmitting={isSubmitting} onCancel={() => navigate(isEdit && id !== undefined ? `/inmuebles/${id}` : '/inmuebles')} onSubmit={submit} owners={owners} />
  </div>
}
