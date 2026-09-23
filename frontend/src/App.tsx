function App() {
  return (
    <main className="min-h-screen bg-slate-950 px-6 py-16 text-slate-100">
      <section className="mx-auto flex max-w-3xl flex-col gap-8">
        <div className="space-y-4">
          <p className="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-300">
            FASE 0 · Andamiaje técnico
          </p>
          <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
            Gestión Inmobiliaria
          </h1>
          <p className="max-w-2xl text-lg leading-8 text-slate-300">
            Frontend React independiente listo para crecer por fases junto con
            la API REST de Laravel.
          </p>
        </div>

        <div className="grid gap-4 sm:grid-cols-3">
          {['React + TypeScript', 'Vite + Tailwind CSS', 'API REST separada'].map(
            (technology) => (
              <div
                className="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-sm text-slate-300"
                key={technology}
              >
                {technology}
              </div>
            ),
          )}
        </div>
      </section>
    </main>
  )
}

export default App
