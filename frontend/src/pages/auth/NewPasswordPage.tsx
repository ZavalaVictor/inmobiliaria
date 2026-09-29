import { useState, type FormEvent } from 'react'
import { EyeIcon, LockIcon } from '../../components/auth/AuthIcons.tsx'
import { AuthHeading, AuthPrimaryButton, AuthSecurityFooter, authInputClass, authInputContainerClass, authInputLabelClass } from '../../components/auth/AuthPrimitives.tsx'
import { AuthLayout } from '../../layouts/AuthLayout.tsx'
import { navigate } from '../../router/navigation.ts'
import { resetPassword, type ResetPasswordPayload } from '../../services/auth.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'

interface PasswordFields {
  password: string
  password_confirmation: string
}

interface FieldErrors {
  password?: string
  password_confirmation?: string
}

function getResetParams(): { token: string; email: string } {
  const params = new URLSearchParams(window.location.search)
  return { token: params.get('token') ?? '', email: params.get('email') ?? '' }
}

function validPassword(password: string): boolean {
  return password.length >= 8 && /\p{L}/u.test(password) && /\p{Ll}/u.test(password) && /\p{Lu}/u.test(password) && /\p{N}/u.test(password)
}

function InvalidResetLink(): React.JSX.Element {
  return (
    <AuthLayout panelTitle="Crea una contraseña segura." panelDescription="Solicita un nuevo enlace para volver a acceder a tu cuenta.">
      <div>
        <AuthHeading description="El enlace puede haber expirado, ya fue utilizado o estar incompleto." eyebrow="Enlace no disponible" title="El enlace no es válido" />
        <div className="mt-6"><AuthPrimaryButton onClick={() => navigate('/recuperar-contrasena')}>Solicitar un nuevo enlace</AuthPrimaryButton></div>
        <div className="mt-8"><AuthSecurityFooter /></div>
      </div>
    </AuthLayout>
  )
}

function getResetError(error: unknown): string {
  if (error instanceof ApiError && error.status === 404) {
    return 'No se encontró la ruta de recuperación. Verifica la configuración del servidor.'
  }

  return 'No pudimos actualizar la contraseña. Intenta nuevamente.'
}

export function NewPasswordPage(): React.JSX.Element {
  const { token, email } = getResetParams()
  const [values, setValues] = useState<PasswordFields>({ password: '', password_confirmation: '' })
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirmation, setShowConfirmation] = useState(false)
  const [invalidLink, setInvalidLink] = useState(false)

  if (!token || !email || invalidLink) {
    return <InvalidResetLink />
  }

  function updateValue(field: keyof PasswordFields, value: string): void {
    setValues((current) => ({ ...current, [field]: value }))
    setFieldErrors((current) => ({ ...current, [field]: undefined }))
    setFormError(null)
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault()
    const errors: FieldErrors = {}

    if (!values.password) {
      errors.password = 'Ingresa una nueva contraseña.'
    } else if (!validPassword(values.password)) {
      errors.password = 'Usa al menos 8 caracteres, letras mayúsculas y minúsculas, y un número.'
    }

    if (!values.password_confirmation) {
      errors.password_confirmation = 'Confirma tu nueva contraseña.'
    } else if (values.password !== values.password_confirmation) {
      errors.password_confirmation = 'Las contraseñas no coinciden.'
    }

    setFieldErrors(errors)
    setFormError(null)

    if (Object.keys(errors).length > 0) {
      return
    }

    setIsSubmitting(true)
    const payload: ResetPasswordPayload = { email, token, ...values }

    try {
      await resetPassword(payload)
      navigate('/contrasena-actualizada')
    } catch (error: unknown) {
      if (error instanceof ApiError && error.status === 422) {
        const serverErrors = getValidationErrors(error.payload)
        if (Object.keys(serverErrors).length === 0) {
          setInvalidLink(true)
        } else {
          setFieldErrors({
            password: serverErrors.password?.[0],
            password_confirmation: serverErrors.password_confirmation?.[0],
          })
          setFormError('Revisa los datos ingresados.')
        }
      } else {
        setFormError(getResetError(error))
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthLayout panelTitle="Crea una contraseña segura." panelDescription="Actualiza tu contraseña para volver a acceder a tu cuenta de forma segura.">
      <AuthHeading description="Elige una contraseña segura para volver a acceder a tu cuenta." title="Crea una nueva contraseña" />

      <form className="mt-8 space-y-5" noValidate onSubmit={handleSubmit}>
        {formError ? <div aria-live="assertive" className="rounded-xl border border-[#F2C7C7] bg-[#FFF5F5] px-4 py-3 text-sm font-medium text-[#A33A3A]">{formError}</div> : null}
        <PasswordField error={fieldErrors.password} id="new-password" label="Nueva contraseña" onChange={(value) => updateValue('password', value)} show={showPassword} toggle={() => setShowPassword((current) => !current)} value={values.password} />
        <PasswordField error={fieldErrors.password_confirmation} id="password-confirmation" label="Confirmar contraseña" onChange={(value) => updateValue('password_confirmation', value)} show={showConfirmation} toggle={() => setShowConfirmation((current) => !current)} value={values.password_confirmation} />
        <p className="text-sm leading-6 text-[#71849C]">La contraseña debe tener al menos 8 caracteres, letras mayúsculas y minúsculas, y un número.</p>
        <AuthPrimaryButton isLoading={isSubmitting} loadingLabel="Actualizando..." type="submit">Actualizar contraseña</AuthPrimaryButton>
      </form>
      <a className="mt-5 block text-center text-base font-medium text-[#0F8B87] underline-offset-4 hover:underline focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0F766E] sm:text-[17px]" href="/login" onClick={(event) => { event.preventDefault(); navigate('/login') }}>Volver al inicio de sesión</a>
      <div className="mt-8"><AuthSecurityFooter /></div>
    </AuthLayout>
  )
}

function PasswordField({ error, id, label, onChange, show, toggle, value }: { error?: string; id: string; label: string; onChange: (value: string) => void; show: boolean; toggle: () => void; value: string }): React.JSX.Element {
  const errorId = `${id}-error`

  return (
    <div className="space-y-1.5">
      <label className={authInputLabelClass} htmlFor={id}>{label}</label>
      <div className={`${authInputContainerClass} ${error ? 'border-[#C33E3E]' : 'border-[#C7D5E0] focus-within:border-[#0F766E]'}`}>
        <span className="text-[#697C94]"><LockIcon /></span>
        <input aria-describedby={error ? errorId : undefined} aria-invalid={Boolean(error)} autoComplete="new-password" className={authInputClass} id={id} name={id} onChange={(event) => onChange(event.target.value)} placeholder="••••••••" type={show ? 'text' : 'password'} value={value} />
        <button aria-label={show ? `Ocultar ${label.toLowerCase()}` : `Mostrar ${label.toLowerCase()}`} className="rounded-md p-1 text-[#697C94] outline-none transition hover:text-[#0F766E] focus-visible:ring-2 focus-visible:ring-[#0F766E]" onClick={toggle} type="button"><EyeIcon hidden={show} /></button>
      </div>
      {error ? <p className="text-sm font-medium text-[#A33A3A]" id={errorId}>{error}</p> : null}
    </div>
  )
}
