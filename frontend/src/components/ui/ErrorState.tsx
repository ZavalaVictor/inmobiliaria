interface ErrorStateProps {
  title?: string
  message?: string
  onRetry?: () => void
}

export function ErrorState({ title = 'No pudimos cargar la información', message = 'Intenta nuevamente en unos momentos.', onRetry }: ErrorStateProps): React.JSX.Element {
  return (
    <div aria-live="polite" className="rounded-2xl border border-[var(--app-danger-border)] bg-[var(--app-danger-surface)] p-6 text-center">
      <p className="text-sm font-semibold uppercase tracking-[0.12em] text-[var(--app-danger)]">Ocurrió un problema</p>
      <h2 className="mt-2 text-lg font-bold text-[var(--app-text)]">{title}</h2>
      <p className="mt-2 text-sm text-[var(--app-text-muted)]">{message}</p>
      {onRetry ? <button className="app-button-primary mt-5" onClick={onRetry} type="button">Intentar nuevamente</button> : null}
    </div>
  )
}
