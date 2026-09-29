import { useState, type FormEvent } from 'react'
import { MailIcon } from '../../components/auth/AuthIcons.tsx'
import { AuthHeading, AuthPrimaryButton, AuthSecurityFooter, authInputClass, authInputContainerClass, authInputLabelClass } from '../../components/auth/AuthPrimitives.tsx'
import { AuthLayout } from '../../layouts/AuthLayout.tsx'
import { navigate } from '../../router/navigation.ts'
import { forgotPassword } from '../../services/auth.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'

function validEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())
}

function getRequestError(error: unknown): string {
  if (error instanceof ApiError && error.status === 404) {
    return 'No se encontró la ruta de recuperación. Verifica la configuración del servidor.'
  }

  if (error instanceof ApiError && error.status === 429) {
    return 'No pudimos procesar la solicitud en este momento. Intenta nuevamente más tarde.'
  }

  return 'No pudimos procesar la solicitud. Intenta nuevamente.'
}

export function PasswordRecoveryPage(): React.JSX.Element {
  const [email, setEmail] = useState('')
  const [fieldError, setFieldError] = useState<string | null>(null)
  const [formError, setFormError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault()
    const normalizedEmail = email.trim()
    setFieldError(null)
    setFormError(null)

    if (!normalizedEmail) {
      setFieldError('Ingresa tu correo electrónico.')
      return
    }

    if (!validEmail(normalizedEmail)) {
      setFieldError('Ingresa un correo electrónico válido.')
      return
    }

    setIsSubmitting(true)

    try {
      await forgotPassword(normalizedEmail)
      navigate('/revisa-tu-correo')
    } catch (error: unknown) {
      if (error instanceof ApiError && error.status === 422) {
        setFieldError(getValidationErrors(error.payload).email?.[0] ?? 'Ingresa un correo electrónico válido.')
      } else {
        setFormError(getRequestError(error))
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthLayout panelTitle="Recupera el acceso a tu cuenta." panelDescription="Te ayudaremos a restablecer tu contraseña de forma segura.">
      <AuthHeading description="Ingresa tu correo electrónico para solicitar un enlace de recuperación." title="Recuperar contraseña" />

      <form className="mt-8 space-y-5" noValidate onSubmit={handleSubmit}>
        {formError ? <div aria-live="assertive" className="rounded-xl border border-[#F2C7C7] bg-[#FFF5F5] px-4 py-3 text-sm font-medium text-[#A33A3A]">{formError}</div> : null}
        <div className="space-y-1.5">
          <label className={authInputLabelClass} htmlFor="recovery-email">Correo electrónico</label>
          <div className={`${authInputContainerClass} ${fieldError ? 'border-[#C33E3E]' : 'border-[#C7D5E0] focus-within:border-[#0F766E]'}`}>
            <span className="text-[#697C94]"><MailIcon /></span>
            <input aria-describedby={fieldError ? 'recovery-email-error' : undefined} aria-invalid={Boolean(fieldError)} autoComplete="email" className={authInputClass} id="recovery-email" name="email" onChange={(event) => { setEmail(event.target.value); setFieldError(null); setFormError(null) }} placeholder="nombre@correo.com" type="email" value={email} />
          </div>
          {fieldError ? <p className="text-sm font-medium text-[#A33A3A]" id="recovery-email-error">{fieldError}</p> : null}
        </div>
        <a className="inline-block text-base font-medium text-[#0F8B87] underline-offset-4 transition hover:text-[#0B5D5A] hover:underline focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0F766E] sm:text-[17px]" href="/login" onClick={(event) => { event.preventDefault(); navigate('/login') }}>Volver al inicio de sesión</a>
        <AuthPrimaryButton isLoading={isSubmitting} loadingLabel="Enviando..." type="submit">Enviar enlace de recuperación</AuthPrimaryButton>
      </form>

      <div className="mt-8"><AuthSecurityFooter label="Solicitud segura" /></div>
    </AuthLayout>
  )
}
