import { useState, type FormEvent } from 'react'
import { AuthHeading, AuthPrimaryButton, AuthSecurityFooter, authInputClass, authInputContainerClass, authInputLabelClass } from '../../components/auth/AuthPrimitives.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { AuthLayout } from '../../layouts/AuthLayout.tsx'
import { getInitialRoute, navigate } from '../../router/navigation.ts'
import { ApiError, getValidationErrors } from '../../services/http.ts'

interface LoginValues {
  email: string
  password: string
}

interface FieldErrors {
  email?: string
  password?: string
}

function MailIcon(): React.JSX.Element {
  return <svg aria-hidden="true" className="size-5" fill="none" viewBox="0 0 24 24"><path d="m3 6 9 7 9-7M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" /></svg>
}

function LockIcon(): React.JSX.Element {
  return <svg aria-hidden="true" className="size-5" fill="none" viewBox="0 0 24 24"><rect height="10" rx="1.5" stroke="currentColor" strokeWidth="1.8" width="14" x="5" y="10" /><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" /></svg>
}

function EyeIcon({ hidden }: { hidden: boolean }): React.JSX.Element {
  return <svg aria-hidden="true" className="size-5" fill="none" viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" />{hidden ? <path d="m4 4 16 16" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" /> : <circle cx="12" cy="12" r="2.5" stroke="currentColor" strokeWidth="1.8" />}</svg>
}

function validate(values: LoginValues): FieldErrors {
  const errors: FieldErrors = {}

  if (!values.email.trim()) {
    errors.email = 'Ingresa tu correo electrónico.'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email.trim())) {
    errors.email = 'Ingresa un correo electrónico válido.'
  }

  if (!values.password) {
    errors.password = 'Ingresa tu contraseña.'
  }

  return errors
}

function getLoginError(error: unknown): string {
  if (error instanceof ApiError) {
    if (error.status === 401) {
      return error.message
    }

    if (error.status === 422) {
      return 'Revisa los datos ingresados.'
    }

    if (error.status === 404) {
      return 'No se encontró la ruta de autenticación. Verifica la configuración del proxy.'
    }

    if (error.status >= 500) {
      return 'No pudimos conectarnos con el servidor. Intenta nuevamente.'
    }

    return 'No pudimos iniciar sesión. Intenta nuevamente.'
  }

  return 'No pudimos conectarnos con el servidor. Intenta nuevamente.'
}

export function LoginPage(): React.JSX.Element {
  const { clearNotice, login, notice } = useAuth()
  const [values, setValues] = useState<LoginValues>({ email: '', password: '' })
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [showPassword, setShowPassword] = useState(false)

  function updateValue(field: keyof LoginValues, value: string): void {
    setValues((current) => ({ ...current, [field]: value }))
    setFieldErrors((current) => ({ ...current, [field]: undefined }))
    setFormError(null)
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault()
    clearNotice()

    const validationErrors = validate(values)
    setFieldErrors(validationErrors)
    setFormError(null)

    if (Object.keys(validationErrors).length > 0) {
      return
    }

    setIsSubmitting(true)

    try {
      const authenticatedUser = await login(values.email.trim(), values.password)
      navigate(getInitialRoute(authenticatedUser))
    } catch (error: unknown) {
      if (error instanceof ApiError && error.status === 422) {
        const serverErrors = getValidationErrors(error.payload)
        setFieldErrors({
          email: serverErrors.email?.[0],
          password: serverErrors.password?.[0],
        })
      }

      setFormError(getLoginError(error))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthLayout>
      <AuthHeading description="Ingresa tus datos para acceder a SotyTech." title="Bienvenido de nuevo" />

      <form className="mt-8 space-y-5" noValidate onSubmit={handleSubmit}>
        {notice ? <div aria-live="polite" className="rounded-xl border border-[#F1D9A7] bg-[#FFF9EC] px-4 py-3 text-sm font-medium text-[#7D5A13]">{notice}</div> : null}
        {formError ? <div aria-live="assertive" className="rounded-xl border border-[#F2C7C7] bg-[#FFF5F5] px-4 py-3 text-sm font-medium text-[#A33A3A]">{formError}</div> : null}

        <div className="space-y-1.5">
          <label className={authInputLabelClass} htmlFor="email">Correo electrónico</label>
          <div className={`${authInputContainerClass} ${fieldErrors.email ? 'border-[#C33E3E]' : 'border-[#C7D5E0] focus-within:border-[#0F766E]'}`}>
            <span className="text-[#697C94]"><MailIcon /></span>
            <input aria-describedby={fieldErrors.email ? 'email-error' : undefined} aria-invalid={Boolean(fieldErrors.email)} autoComplete="email" className={authInputClass} id="email" name="email" onChange={(event) => updateValue('email', event.target.value)} placeholder="nombre@correo.com" type="email" value={values.email} />
          </div>
          {fieldErrors.email ? <p className="text-sm font-medium text-[#A33A3A]" id="email-error">{fieldErrors.email}</p> : null}
        </div>

        <div className="space-y-1.5">
          <label className={authInputLabelClass} htmlFor="password">Contraseña</label>
          <div className={`${authInputContainerClass} ${fieldErrors.password ? 'border-[#C33E3E]' : 'border-[#C7D5E0] focus-within:border-[#0F766E]'}`}>
            <span className="text-[#697C94]"><LockIcon /></span>
            <input aria-describedby={fieldErrors.password ? 'password-error' : undefined} aria-invalid={Boolean(fieldErrors.password)} autoComplete="current-password" className={authInputClass} id="password" name="password" onChange={(event) => updateValue('password', event.target.value)} placeholder="••••••••" type={showPassword ? 'text' : 'password'} value={values.password} />
            <button aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'} className="rounded-md p-1 text-[#697C94] outline-none transition hover:text-[#0F766E] focus-visible:ring-2 focus-visible:ring-[#0F766E]" onClick={() => setShowPassword((current) => !current)} type="button"><EyeIcon hidden={showPassword} /></button>
          </div>
          {fieldErrors.password ? <p className="text-sm font-medium text-[#A33A3A]" id="password-error">{fieldErrors.password}</p> : null}
        </div>

        <div className="flex justify-end">
          <a className="text-base font-medium text-[#0F8B87] underline-offset-4 transition hover:text-[#0B5D5A] hover:underline focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0F766E] sm:text-[17px]" href="/recuperar-contrasena" onClick={(event) => { event.preventDefault(); navigate('/recuperar-contrasena') }}>¿Olvidaste tu contraseña?</a>
        </div>

        <AuthPrimaryButton isLoading={isSubmitting} loadingLabel="Iniciando sesión..." type="submit">Iniciar sesión</AuthPrimaryButton>
      </form>

      <div className="mt-8"><AuthSecurityFooter showShield /></div>
    </AuthLayout>
  )
}
