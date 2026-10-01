import type { InmuebleListItem } from '../../types/inmuebles.ts'

const moneyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 2 })

function formatPropertyPrice(value: string | number | null): string {
  if (value === null || value === '') {
    return '—'
  }

  const numericValue = typeof value === 'number' ? value : Number(value)
  return Number.isFinite(numericValue) ? moneyFormatter.format(numericValue) : '—'
}

export function PropertyPrice({ property }: { property: InmuebleListItem }): React.JSX.Element {
  const value = property.tipo_operacion === 'venta' ? property.precio_venta : property.renta_mensual
  return <span className="font-semibold text-[var(--app-text)]">{formatPropertyPrice(value)}</span>
}
