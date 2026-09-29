import { CheckCircleIcon } from '../../components/auth/AuthIcons.tsx'
import { AuthHeading, AuthPrimaryButton, AuthSecurityFooter } from '../../components/auth/AuthPrimitives.tsx'
import { AuthLayout } from '../../layouts/AuthLayout.tsx'
import { navigate } from '../../router/navigation.ts'

export function PasswordUpdatedPage(): React.JSX.Element {
  return (
    <AuthLayout panelTitle="Acceso recuperado." panelDescription="Tu contraseña ha sido actualizada correctamente y ya puedes volver a ingresar a tu cuenta.">
      <AuthHeading description="Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión con tu nueva contraseña." title="¡Contraseña actualizada!" />
      <div className="mt-6 flex items-center gap-4 rounded-xl border border-[#A9E0D5] bg-[#F1FCFB] p-4 text-[#0F5F62]">
        <span className="grid size-12 shrink-0 place-items-center rounded-full bg-[#47BBA9]"><CheckCircleIcon /></span>
        <div><h2 className="text-base font-bold">Cambio realizado</h2><p className="mt-1 text-sm leading-6 text-[#6D8498]">La nueva contraseña se guardó de forma segura.</p></div>
      </div>
      <div className="mt-6"><AuthPrimaryButton onClick={() => navigate('/login')}>Volver al inicio de sesión</AuthPrimaryButton></div>
      <a className="mt-4 block text-center text-base font-medium text-[#0F8B87] underline-offset-4 hover:underline focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0F766E] sm:text-[17px]" href="/login" onClick={(event) => { event.preventDefault(); navigate('/login') }}>Ir al login</a>
      <div className="mt-8"><AuthSecurityFooter /></div>
    </AuthLayout>
  )
}
