export function LoadingState(): React.JSX.Element {
  return <div className="public-state" role="status"><span className="public-spinner" /> <span>Consultando propiedades...</span></div>
}

export function ErrorState({ onRetry, detail = 'Intenta nuevamente en unos momentos.' }: { onRetry: () => void; detail?: string }): React.JSX.Element {
  return <div className="public-state public-state-error" role="alert"><p className="font-bold">No pudimos cargar la información</p><p className="mt-2 text-sm">{detail}</p><button className="public-button-primary mt-5" onClick={onRetry} type="button">Intentar nuevamente</button></div>
}

export function EmptyState({ filtered }: { filtered: boolean }): React.JSX.Element {
  return <div className="public-state"><p className="text-lg font-bold text-[var(--public-text)]">No encontramos propiedades</p><p className="mt-2 text-sm text-[var(--public-muted)]">{filtered ? 'Prueba con otros criterios de búsqueda.' : 'Estamos preparando nuevas opciones para ti.'}</p></div>
}
