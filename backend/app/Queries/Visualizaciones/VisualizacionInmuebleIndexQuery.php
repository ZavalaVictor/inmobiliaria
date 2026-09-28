<?php

namespace App\Queries\Visualizaciones;

use Illuminate\Database\Eloquent\Builder;

final class VisualizacionInmuebleIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach (['inmueble_id', 'origen'] as $field) {
            if (array_key_exists($field, $this->filters)) {
                $query->where($field, $this->filters[$field]);
            }
        }

        foreach ([
            'fecha_desde' => ['fecha_visualizacion', '>='],
            'fecha_hasta' => ['fecha_visualizacion', '<='],
        ] as $filter => [$column, $operator]) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($column, $operator, $this->filters[$filter]);
            }
        }

        $allowedSorts = [
            'id',
            'inmueble_id',
            'origen',
            'fecha_visualizacion',
            'created_at',
        ];
        $sort = in_array($this->filters['sort'] ?? null, $allowedSorts, true)
            ? $this->filters['sort']
            : 'fecha_visualizacion';
        $direction = ($this->filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction);
    }
}
