import { useEffect, useState } from 'react'
import { getUnreadNotificationCount } from '../../services/notifications.ts'

function BellIcon({ className }: { className?: string }): React.JSX.Element {
  return <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" /></svg>
}

export function NotificationBadge(): React.JSX.Element {
  const [count, setCount] = useState<number | null>(null)

  useEffect(() => {
    let active = true

    getUnreadNotificationCount()
      .then((nextCount) => {
        if (active) {
          setCount(nextCount)
        }
      })
      .catch(() => {
        // El contador es auxiliar; un fallo no debe bloquear la shell.
      })

    const handleFocus = (): void => {
      getUnreadNotificationCount()
        .then((nextCount) => {
          if (active) {
            setCount(nextCount)
          }
        })
        .catch(() => undefined)
    }

    window.addEventListener('focus', handleFocus)

    return () => {
      active = false
      window.removeEventListener('focus', handleFocus)
    }
  }, [])

  return (
    <div aria-label={count && count > 0 ? `${count} notificaciones sin leer` : 'Notificaciones'} className="app-icon-button relative" role="status">
      <BellIcon className="size-5" />
      {count !== null && count > 0 ? <span aria-hidden="true" className="absolute -right-1 -top-1 grid min-h-5 min-w-5 place-items-center rounded-full bg-[var(--app-danger)] px-1 text-[10px] font-bold text-white">{count > 99 ? '99+' : count}</span> : null}
    </div>
  )
}
