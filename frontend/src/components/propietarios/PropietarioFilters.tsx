import { useState } from 'react'
import type { PropietarioFilters as PropietarioFilterState, PropietarioSort } from '../../types/propietarios.ts'

interface PropietarioFiltersProps {
  filters: PropietarioFilterState
  onChange: (field: keyof PropietarioFilterState, value: string) => void
  onClear: () => void
}

const sortLabels: Record<PropietarioSort, string> = {
  created_at: 'Más recientes',
  updated_at: 'Última actualización',
  id: 'ID',
  nombre_razon_social: 'Nombre',
  rfc: 'RFC',
}

function Field({ children, label }: { children: React.ReactNode; label: string }): React.JSX.Element {
  return <label className="flex min-w-0 flex-col gap-1.5 text-sm font-semibold text-[var(--app-text)]"><span>{label}</span>{children}</label>
}

export function PropietarioFilters({ filters, onChange, onClear }: PropietarioFiltersProps): React.JSX.Element {
  const [advancedOpen, setAdvancedOpen] = useState(false)

  return <section aria-label="Filtros de propietarios" className="app-card p-4 sm:p-5">
    <div className="flex flex-col gap-3 lg:flex-row lg:items-end">
      <Field label="Buscar"><input aria-label="Buscar por nombre, RFC, email o teléfono" className="app-input" onChange={(event) => onChange('q', event.target.value)} placeholder="Buscar por nombre, RFC, email o teléfono" type="search" value={filters.q} /></Field>
      <div className="flex shrink-0 flex-wrap gap-2">
        <button aria-expanded={advancedOpen} className="app-button-secondary" onClick={() => setAdvancedOpen((current) => !current)} type="button">{advancedOpen ? 'Ocultar filtros' : 'Más filtros'} <span aria-hidden="true">⌄</span></button>
        <button className="app-button-secondary" onClick={onClear} type="button">Limpiar filtros</button>
      </div>
    </div>
    <div className="mt-4 grid gap-4 border-t border-[var(--app-border)] pt-4 md:grid-cols-2 xl:grid-cols-4">
      <Field label="Tipo de persona"><select className="app-select w-full" onChange={(event) => onChange('tipo_persona', event.target.value)} value={filters.tipo_persona}><option value="">Todos</option><option value="fisica">Persona física</option><option value="moral">Persona moral</option></select></Field>
      <Field label="Estado"><select className="app-select w-full" onChange={(event) => onChange('estado_registro', event.target.value)} value={filters.estado_registro}><option value="">Todos</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></Field>
    </div>
    {advancedOpen ? <div className="mt-4 grid gap-4 border-t border-[var(--app-border)] pt-4 md:grid-cols-2 xl:grid-cols-4">
      <Field label="Ordenar por"><select className="app-select w-full" onChange={(event) => onChange('sort', event.target.value)} value={filters.sort}>{Object.entries(sortLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
      <Field label="Dirección"><select className="app-select w-full" onChange={(event) => onChange('direction', event.target.value)} value={filters.direction}><option value="desc">Descendente</option><option value="asc">Ascendente</option></select></Field>
    </div> : null}
    <p className="mt-4 text-xs text-[var(--app-text-muted)]">Los filtros se aplican automáticamente.</p>
  </section>
}
