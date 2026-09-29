import { EnvelopeCheckIcon } from '../../components/auth/AuthIcons.tsx'
import { AuthHeading, AuthPrimaryButton, AuthSecurityFooter } from '../../components/auth/AuthPrimitives.tsx'
import { AuthLayout } from '../../layouts/AuthLayout.tsx'
import { navigate } from '../../router/navigation.ts'

export function ReviewEmailPage(): React.JSX.Element {
  return (
    <AuthLayout panelTitle="Revisa tu bandeja." panelDescription="Te ayudaremos a recuperar el acceso de forma segura.">
      <AuthHeading description="Si existe una cuenta asociada a ese correo, recibirás un enlace de recuperación." title="Revisa tu correo" />

      <div className="mt-6 flex items-center gap-4 rounded-xl border border-[#87D3CF] bg-[#F1FCFB] p-4 text-[#0F5F62]">
        <span className="grid size-12 shrink-0 place-items-center rounded-full bg-[#D6F5F2]"><EnvelopeCheckIcon /></span>
        <div>
          <h2 className="text-base font-bold">Revisa tu bandeja</h2>
          <p className="mt-1 text-sm leading-6 text-[#6D8498]">Si la cuenta es elegible, el correo incluirá las instrucciones para continuar.</p>
        </div>
      </div>

      <div className="mt-6"><AuthPrimaryButton onClick={() => navigate('/login')}>Volver al inicio de sesión</AuthPrimaryButton></div>
      <p className="mt-4 text-center text-sm text-[#71849C]">¿Necesitas intentarlo de nuevo? <a className="font-medium text-[#0F8B87] underline-offset-4 hover:underline focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0F766E]" href="/recuperar-contrasena" onClick={(event) => { event.preventDefault(); navigate('/recuperar-contrasena') }}>Volver a recuperación</a></p>
      <div className="mt-8"><AuthSecurityFooter label="Solicitud segura" /></div>
    </AuthLayout>
  )
}
