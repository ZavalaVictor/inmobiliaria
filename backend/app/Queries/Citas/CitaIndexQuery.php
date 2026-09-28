<?php

namespace App\Queries\Citas;

use Illuminate\Database\Eloquent\Builder;

final class CitaIndexQuery
{
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach (['estado', 'agente_id', 'cliente_id', 'inmueble_id', 'oportunidad_id'] as $filter) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($filter, $this->filters[$filter]);
            }
        }

        if (array_key_exists('fecha_desde', $this->filters)) {
            $query->where('fecha_fin', '>', $this->filters['fecha_desde']);
        }

        if (array_key_exists('fecha_hasta', $this->filters)) {
            $query->where('fecha_inicio', '<', $this->filters['fecha_hasta']);
        }

        if (array_key_exists('q', $this->filters)) {
            $term = '%'.$this->filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search->where('motivo', 'like', $term)
                    ->orWhere('notas', 'like', $term);
            });
        }

        $allowed = ['id', 'fecha_inicio', 'fecha_fin', 'estado', 'created_at', 'updated_at'];
        $sort = in_array($this->filters['sort'] ?? null, $allowed, true)
            ? $this->filters['sort']
            : 'fecha_inicio';
        $direction = ($this->filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', static fn (Builder $ordered): Builder => $ordered->orderBy('id', 'asc'));
    }
}
