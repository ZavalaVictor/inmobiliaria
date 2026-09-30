interface SimpleBarListItem {
  label: string
  value: number
  suffix?: string
}

interface SimpleBarListProps {
  items: SimpleBarListItem[]
  formatValue?: (value: number) => string
}

export function SimpleBarList({ items, formatValue = (value) => String(value) }: SimpleBarListProps): React.JSX.Element {
  const max = Math.max(...items.map((item) => item.value), 1)

  return (
    <div className="space-y-4">
      {items.map((item) => <div key={`${item.label}-${item.suffix ?? ''}`}>
        <div className="mb-1.5 flex items-center justify-between gap-4 text-sm"><span className="truncate font-medium text-[var(--app-text)]">{item.label}</span><span className="shrink-0 font-semibold text-[var(--app-text-muted)]">{formatValue(item.value)}{item.suffix ?? ''}</span></div>
        <div aria-hidden="true" className="h-2 overflow-hidden rounded-full bg-[var(--app-surface-muted)]"><div className="h-full rounded-full bg-[var(--app-accent)] transition-[width] duration-500" style={{ width: `${(item.value / max) * 100}%` }} /></div>
      </div>)}
    </div>
  )
}
