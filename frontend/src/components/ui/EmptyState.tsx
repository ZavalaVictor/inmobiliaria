interface EmptyStateProps {
  title?: string
  message?: string
  action?: { label: string; onClick: () => void }
}

export function EmptyState({ title = 'Sin información por mostrar', message = 'Cuando existan datos disponibles aparecerán aquí.', action }: EmptyStateProps): React.JSX.Element {
  return (
    <div className="rounded-2xl border border-dashed border-[var(--app-border)] bg-[var(--app-surface-muted)] p-6 text-center">
      <p className="text-base font-semibold text-[var(--app-text)]">{title}</p>
      <p className="mt-2 text-sm text-[var(--app-text-muted)]">{message}</p>
      {action ? <button className="app-button-secondary mt-5" onClick={action.onClick} type="button">{action.label}</button> : null}
    </div>
  )
}
