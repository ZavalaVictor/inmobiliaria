<?php

namespace App\Queries\Bitacora;

use Illuminate\Database\Eloquent\Builder;

final class BitacoraIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach (['user_id', 'accion', 'entidad', 'entidad_id'] as $field) {
            if (array_key_exists($field, $this->filters)) {
                $query->where($field, $this->filters[$field]);
            }
        }

        foreach ([
            'fecha_desde' => ['fecha_evento', '>='],
            'fecha_hasta' => ['fecha_evento', '<='],
        ] as $filter => [$column, $operator]) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($column, $operator, $this->filters[$filter]);
            }
        }

        if (($this->filters['q'] ?? '') !== '') {
            $term = '%'.$this->filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search->where('accion', 'like', $term)
                    ->orWhere('entidad', 'like', $term)
                    ->orWhere('descripcion', 'like', $term);
            });
        }

        $allowedSorts = [
            'id',
            'user_id',
            'accion',
            'entidad',
            'entidad_id',
            'fecha_evento',
            'created_at',
        ];
        $sort = in_array($this->filters['sort'] ?? null, $allowedSorts, true)
            ? $this->filters['sort']
            : 'fecha_evento';
        $direction = ($this->filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', $direction);
    }
}
