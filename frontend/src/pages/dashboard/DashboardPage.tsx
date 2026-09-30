import { useEffect, useState } from 'react'
import { DashboardSection } from '../../components/dashboard/DashboardSection.tsx'
import { MetricCard } from '../../components/dashboard/MetricCard.tsx'
import { SimpleBarList } from '../../components/dashboard/SimpleBarList.tsx'
import { EmptyState } from '../../components/ui/EmptyState.tsx'
import { ErrorState } from '../../components/ui/ErrorState.tsx'
import { LoadingState } from '../../components/ui/LoadingState.tsx'
import { useAuth } from '../../hooks/useAuth.ts'
import { ForbiddenPage } from '../errors/ForbiddenPage.tsx'
import { getDashboard } from '../../services/dashboard.ts'
import { ApiError } from '../../services/http.ts'
import type { DashboardData, DashboardLists, DashboardPeriodKey } from '../../types/dashboard.ts'

const numberFormatter = new Intl.NumberFormat('es-MX')
const moneyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 2 })
const dateFormatter = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' })

function formatNumber(value: number | undefined): string {
  return numberFormatter.format(value ?? 0)
}

function formatMoney(value: number | undefined): string {
  return moneyFormatter.format(value ?? 0)
}

function formatDate(value: string | null): string {
  if (!value) {
    return 'Sin fecha'
  }

  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Sin fecha' : dateFormatter.format(date)
}

function personName(person: { nombres: string; apellido_paterno: string | null; apellido_materno: string | null } | null): string {
  if (!person) {
    return 'Sin asignar'
  }

  return [person.nombres, person.apellido_paterno, person.apellido_materno].filter(Boolean).join(' ')
}

function hasLists(lists: DashboardData['listas']): lists is DashboardLists {
  return !Array.isArray(lists)
}

function metricCards(data: DashboardData): Array<{ label: string; value: string; accent?: 'navy' | 'teal' | 'gold'; detail?: string }> {
  const summary = data.resumen

  if (data.rol === 'Agente Inmobiliario') {
    return [
      { label: 'Inmuebles asignados', value: formatNumber(summary.inmuebles_asignados), accent: 'navy' },
      { label: 'Clientes asignados', value: formatNumber(summary.clientes_asignados) },
      { label: 'Solicitudes en atención', value: formatNumber(summary.solicitudes_en_atencion) },
      { label: 'Citas próximas', value: formatNumber(summary.citas_proximas) },
      { label: 'Oportunidades activas', value: formatNumber(summary.oportunidades_activas), accent: 'gold' },
      { label: 'Operaciones propias', value: formatNumber(summary.operaciones_propias) },
    ]
  }

  if (data.rol === 'Director General') {
    return [
      { label: 'Inmuebles totales', value: formatNumber(summary.inmuebles_totales), accent: 'navy' },
      { label: 'Clientes totales', value: formatNumber(summary.clientes_totales) },
      { label: 'Oportunidades abiertas', value: formatNumber(summary.oportunidades_abiertas), accent: 'gold' },
      { label: 'Operaciones registradas', value: formatNumber(summary.operaciones_registradas) },
      { label: 'Monto registrado', value: formatMoney(summary.monto_operaciones_registradas) },
      { label: 'Solicitudes del periodo', value: formatNumber(summary.solicitudes_periodo) },
      { label: 'Citas del periodo', value: formatNumber(summary.citas_periodo) },
    ]
  }

  return [
    { label: 'Inmuebles totales', value: formatNumber(summary.inmuebles_totales), accent: 'navy' },
    { label: 'Inmuebles disponibles', value: formatNumber(summary.inmuebles_disponibles) },
    { label: 'Clientes totales', value: formatNumber(summary.clientes_totales) },
    { label: 'Oportunidades abiertas', value: formatNumber(summary.oportunidades_abiertas), accent: 'gold' },
    { label: 'Solicitudes nuevas', value: formatNumber(summary.solicitudes_nuevas) },
    { label: 'Citas próximas', value: formatNumber(summary.citas_proximas) },
    { label: 'Operaciones registradas', value: formatNumber(summary.operaciones_registradas) },
    { label: 'Monto registrado', value: formatMoney(summary.monto_operaciones_registradas) },
  ]
}

function DashboardCharts({ data }: { data: DashboardData }): React.JSX.Element {
  const charts = data.graficas
  const stages = charts.oportunidades_por_etapa ?? []
  const states = charts.inmuebles_por_estado ?? []
  const operations = charts.operaciones_por_mes ?? []

  return (
    <div className="grid gap-6 xl:grid-cols-2">
      {stages.length > 0 ? <DashboardSection description="Distribución devuelta por la API." title="Oportunidades por etapa"><SimpleBarList items={stages.map((item) => ({ label: item.etapa, value: item.total }))} formatValue={formatNumber} /></DashboardSection> : null}
      {states.length > 0 ? <DashboardSection description="Estado actual de los inmuebles." title="Inmuebles por estado"><SimpleBarList items={states.map((item) => ({ label: item.estado, value: item.total }))} formatValue={formatNumber} /></DashboardSection> : null}
      {operations.length > 0 ? <DashboardSection description="Serie mensual de los últimos 12 meses." title="Operaciones por mes"><SimpleBarList items={operations.map((item) => ({ label: item.mes, value: item.total, suffix: ` · ${formatMoney(item.monto)}` }))} formatValue={formatNumber} /></DashboardSection> : null}
      {stages.length === 0 && states.length === 0 && operations.length === 0 ? <EmptyState message="No hay gráficas con datos para mostrar en este momento." title="Sin datos agregados" /> : null}
    </div>
  )
}

function DashboardLists({ data }: { data: DashboardData }): React.JSX.Element {
  if (!hasLists(data.listas)) {
    return <EmptyState message="Este perfil recibe métricas ejecutivas sin listas operativas." title="Sin listas operativas" />
  }

  const requests = data.listas.solicitudes_recientes ?? data.listas.solicitudes_pendientes ?? []
  const appointments = data.listas.citas_proximas ?? data.listas.proximas_citas ?? []
  const operations = data.listas.operaciones_recientes ?? []

  return (
    <div className="grid gap-6 xl:grid-cols-2">
      <DashboardSection title="Solicitudes recientes">
        {requests.length === 0 ? <EmptyState message="No hay solicitudes para mostrar." title="Sin solicitudes" /> : <div className="divide-y divide-[var(--app-border)]">{requests.map((item) => <div className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0" key={item.id}><div className="min-w-0"><p className="truncate font-semibold text-[var(--app-text)]">{item.nombre}</p><p className="mt-1 text-xs text-[var(--app-text-muted)]">{item.estado} · {item.origen}</p></div><span className="shrink-0 text-xs text-[var(--app-text-muted)]">{formatDate(item.created_at)}</span></div>)}</div>}
      </DashboardSection>
      <DashboardSection title="Próximas citas">
        {appointments.length === 0 ? <EmptyState message="No hay citas próximas para mostrar." title="Sin citas próximas" /> : <div className="divide-y divide-[var(--app-border)]">{appointments.map((item) => <div className="py-3 first:pt-0 last:pb-0" key={item.id}><div className="flex items-start justify-between gap-4"><p className="font-semibold text-[var(--app-text)]">{formatDate(item.fecha_inicio)}</p><span className="shrink-0 text-xs font-semibold text-[var(--app-accent)]">{item.estado}</span></div><p className="mt-1 truncate text-sm text-[var(--app-text-muted)]">{item.inmueble?.titulo ?? 'Inmueble no disponible'} · {personName(item.cliente)}</p></div>)}</div>}
      </DashboardSection>
      {data.rol === 'Agente Inmobiliario' ? <DashboardSection title="Operaciones recientes">
        {operations.length === 0 ? <EmptyState message="No hay operaciones relacionadas para mostrar." title="Sin operaciones" /> : <div className="divide-y divide-[var(--app-border)]">{operations.map((item) => <div className="py-3 first:pt-0 last:pb-0" key={item.id}><div className="flex items-start justify-between gap-4"><p className="font-semibold text-[var(--app-text)]">{item.inmueble?.titulo ?? 'Inmueble no disponible'}</p><span className="shrink-0 text-xs text-[var(--app-text-muted)]">{item.estado}</span></div><p className="mt-1 text-sm text-[var(--app-text-muted)]">{personName(item.cliente)} · {formatDate(item.fecha_operacion)}</p></div>)}</div>}
      </DashboardSection> : null}
    </div>
  )
}

export function DashboardPage(): React.JSX.Element {
  const { user } = useAuth()
  const [period, setPeriod] = useState<DashboardPeriodKey>('mes')
  const [data, setData] = useState<DashboardData | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<unknown>(null)
  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    let active = true

    getDashboard(period)
      .then((nextData) => {
        if (active) {
          setData(nextData)
        }
      })
      .catch((nextError: unknown) => {
        if (active) {
          setError(nextError)
        }
      })
      .finally(() => {
        if (active) {
          setIsLoading(false)
        }
      })

    return () => {
      active = false
    }
  }, [period, reloadKey])

  const reload = (): void => {
    setIsLoading(true)
    setError(null)
    setReloadKey((current) => current + 1)
  }

  if (isLoading && !data) {
    return <LoadingState label="Cargando tu dashboard..." />
  }

  if (error && !data) {
    const isForbidden = error instanceof ApiError && error.status === 403
    if (isForbidden) {
      return <ForbiddenPage />
    }

    return <ErrorState onRetry={reload} />
  }

  if (!data) {
    return <EmptyState title="Dashboard sin datos" />
  }

  const cards = metricCards(data)

  return (
    <div className="space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p className="text-sm font-medium text-[var(--app-text-muted)]">Hola, {user?.nombres ?? 'bienvenido'}</p><h2 className="mt-1 text-2xl font-bold tracking-[-0.035em] text-[var(--app-text)] sm:text-3xl">Resumen de tu operación</h2><p className="mt-2 text-sm text-[var(--app-text-muted)]">Periodo: {data.periodo.inicio} — {data.periodo.fin}</p></div>
        <label className="flex items-center gap-3 text-sm font-semibold text-[var(--app-text-muted)]">Periodo<select className="app-select" onChange={(event) => { setIsLoading(true); setError(null); setPeriod(event.target.value as DashboardPeriodKey) }} value={period}><option value="mes">Mes</option><option value="trimestre">Trimestre</option><option value="anio">Año</option></select></label>
      </div>

      {error ? <ErrorState message="Mostramos la última información disponible." onRetry={reload} title="No pudimos actualizar el dashboard" /> : null}

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{cards.map((card) => <MetricCard {...card} key={card.label} />)}</div>

      <DashboardCharts data={data} />
      <DashboardLists data={data} />
    </div>
  )
}
