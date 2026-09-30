interface MetricCardProps {
  label: string
  value: string
  detail?: string
  accent?: 'navy' | 'teal' | 'gold'
}

export function MetricCard({ label, value, detail, accent = 'teal' }: MetricCardProps): React.JSX.Element {
  return (
    <article className="app-card relative overflow-hidden p-5 sm:p-6">
      <span className={`absolute inset-y-0 left-0 w-1 ${accent === 'navy' ? 'bg-[var(--app-navy)]' : accent === 'gold' ? 'bg-[#C88A2B]' : 'bg-[var(--app-accent)]'}`} />
      <p className="text-sm font-semibold text-[var(--app-text-muted)]">{label}</p>
      <p className="mt-3 text-2xl font-bold tracking-[-0.03em] text-[var(--app-text)] sm:text-3xl">{value}</p>
      {detail ? <p className="mt-2 text-xs text-[var(--app-text-muted)]">{detail}</p> : null}
    </article>
  )
}
