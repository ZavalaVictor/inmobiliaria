import { useState } from 'react'
import type { CategoriaOption } from '../../types/categorias.ts'
import type { InmuebleFilters as InmuebleFilterState, InmuebleSort } from '../../types/inmuebles.ts'
import type { PropietarioOption } from '../../types/propietarios.ts'

interface InmuebleFiltersProps {
  filters: InmuebleFilterState
  categories: CategoriaOption[] | null
  owners: PropietarioOption[] | null
  catalogsLoading: boolean
  onChange: (field: keyof InmuebleFilterState, value: string) => void
  onClear: () => void
}

const sortLabels: Record<InmuebleSort, string> = {
  created_at: 'Más recientes',
  updated_at: 'Última actualización',
  id: 'ID',
  codigo: 'Código',
  titulo: 'Título',
  tipo_operacion: 'Operación',
  estado_disponibilidad: 'Estado',
}

function Field({ children, label }: { children: React.ReactNode; label: string }): React.JSX.Element {
  return <label className="flex min-w-0 flex-col gap-1.5 text-sm font-semibold text-[var(--app-text)]"><span>{label}</span>{children}</label>
}

export function InmuebleFilters({ filters, categories, owners, catalogsLoading, onChange, onClear }: InmuebleFiltersProps): React.JSX.Element {
  const [advancedOpen, setAdvancedOpen] = useState(false)

  return <section aria-label="Filtros de inmuebles" className="app-card p-4 sm:p-5">
    <div className="flex flex-col gap-3 lg:flex-row lg:items-end">
      <Field label="Buscar"><input aria-label="Buscar por código, título, descripción o ubicación" className="app-input" onChange={(event) => onChange('q', event.target.value)} placeholder="Buscar por código, título, descripción o ubicación" type="search" value={filters.q} /></Field>
      <div className="flex shrink-0 flex-wrap gap-2">
        <button aria-expanded={advancedOpen} className="app-button-secondary" onClick={() => setAdvancedOpen((current) => !current)} type="button">{advancedOpen ? 'Ocultar filtros' : 'Más filtros'} <span aria-hidden="true">⌄</span></button>
        <button className="app-button-secondary" onClick={onClear} type="button">Limpiar filtros</button>
      </div>
    </div>
    <div className="mt-4 grid gap-4 border-t border-[var(--app-border)] pt-4 md:grid-cols-2 xl:grid-cols-4">
      <Field label="Tipo de operación"><select className="app-select w-full" onChange={(event) => onChange('tipo_operacion', event.target.value)} value={filters.tipo_operacion}><option value="">Todas las operaciones</option><option value="venta">Venta</option><option value="renta">Renta</option></select></Field>
      <Field label="Estado"><select className="app-select w-full" onChange={(event) => onChange('estado_disponibilidad', event.target.value)} value={filters.estado_disponibilidad}><option value="">Todos los estados</option><option value="disponible">Disponible</option><option value="vendido">Vendido</option><option value="rentado">Rentado</option><option value="inactivo">Inactivo</option></select></Field>
      <Field label="Publicado"><select className="app-select w-full" onChange={(event) => onChange('publicado', event.target.value)} value={filters.publicado}><option value="">Todos</option><option value="true">Sí</option><option value="false">No</option></select></Field>
      <Field label="Categoría"><select className="app-select w-full" disabled={catalogsLoading || categories === null} onChange={(event) => onChange('categoria_id', event.target.value)} value={filters.categoria_id}><option value="">{catalogsLoading ? 'Cargando categorías…' : categories === null ? 'No disponible' : 'Todas las categorías'}</option>{categories?.map((category) => <option key={category.id} value={category.id}>{category.nombre}</option>)}</select></Field>
    </div>
    {advancedOpen ? <div className="mt-4 grid gap-4 border-t border-[var(--app-border)] pt-4 md:grid-cols-2 xl:grid-cols-4">
      <Field label="Propietario"><select className="app-select w-full" disabled={catalogsLoading || owners === null} onChange={(event) => onChange('propietario_id', event.target.value)} value={filters.propietario_id}><option value="">{catalogsLoading ? 'Cargando propietarios…' : owners === null ? 'No disponible' : 'Todos los propietarios'}</option>{owners?.map((owner) => <option key={owner.id} value={owner.id}>{owner.nombre_razon_social}</option>)}</select></Field>
      <Field label="Habitaciones"><input className="app-input" min="0" onChange={(event) => onChange('habitaciones', event.target.value)} placeholder="Cualquiera" type="number" value={filters.habitaciones} /></Field>
      <Field label="Baños completos"><input className="app-input" min="0" onChange={(event) => onChange('banos_completos', event.target.value)} placeholder="Cualquiera" type="number" value={filters.banos_completos} /></Field>
      <Field label="Municipio"><input className="app-input" onChange={(event) => onChange('municipio', event.target.value)} placeholder="Todos los municipios" type="text" value={filters.municipio} /></Field>
      <Field label="Estado de ubicación"><input className="app-input" onChange={(event) => onChange('estado_ubicacion', event.target.value)} placeholder="Todos los estados" type="text" value={filters.estado_ubicacion} /></Field>
      <Field label="Ordenar por"><select className="app-select w-full" onChange={(event) => onChange('sort', event.target.value)} value={filters.sort}>{Object.entries(sortLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
      <Field label="Dirección"><select className="app-select w-full" onChange={(event) => onChange('direction', event.target.value)} value={filters.direction}><option value="desc">Descendente</option><option value="asc">Ascendente</option></select></Field>
    </div> : null}
    <p className="mt-4 text-xs text-[var(--app-text-muted)]">Los filtros se aplican automáticamente.</p>
  </section>
}
