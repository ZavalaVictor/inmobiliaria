import type { PropsWithChildren, ReactNode } from 'react'

interface DashboardSectionProps extends PropsWithChildren {
  title: string
  description?: string
  action?: ReactNode
}

export function DashboardSection({ title, description, action, children }: DashboardSectionProps): React.JSX.Element {
  return (
    <section className="app-card p-5 sm:p-6">
      <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div><h2 className="text-base font-bold text-[var(--app-text)] sm:text-lg">{title}</h2>{description ? <p className="mt-1 text-sm text-[var(--app-text-muted)]">{description}</p> : null}</div>
        {action}
      </div>
      {children}
    </section>
  )
}
