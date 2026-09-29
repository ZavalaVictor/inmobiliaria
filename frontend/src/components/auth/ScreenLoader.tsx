export function ScreenLoader(): React.JSX.Element {
  return (
    <main className="grid min-h-screen place-items-center bg-[#F7FAFC] px-6">
      <div aria-label="Cargando" className="flex items-center gap-3 text-sm font-medium text-slate-600" role="status">
        <span className="size-5 animate-spin rounded-full border-2 border-[#0F766E]/20 border-t-[#0F766E]" />
        Verificando sesión...
      </div>
    </main>
  )
}
