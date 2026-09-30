interface LoadingStateProps {
  label?: string
  compact?: boolean
}

export function LoadingState({ label = 'Cargando información...', compact = false }: LoadingStateProps): React.JSX.Element {
  return (
    <div aria-label={label} className={`flex items-center gap-3 text-sm font-medium text-[var(--app-text-muted)] ${compact ? 'py-4' : 'min-h-48 justify-center'}`} role="status">
      <span className="size-5 animate-spin rounded-full border-2 border-[var(--app-accent-soft)] border-t-[var(--app-accent)]" />
      <span>{label}</span>
    </div>
  )
}
