<?php

namespace App\Queries\Operaciones;

use Illuminate\Database\Eloquent\Builder;

final class OperacionIndexQuery
{
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach (['tipo_operacion', 'estado', 'cliente_id', 'inmueble_id', 'oportunidad_id'] as $filter) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($filter, $this->filters[$filter]);
            }
        }

        if (array_key_exists('agente_id', $this->filters)) {
            $query->whereHas('asignacionesAgentes', fn (Builder $assignment): Builder => $assignment->where('agente_id', $this->filters['agente_id']));
        }

        foreach ([
            'fecha_operacion_desde' => ['fecha_operacion', '>='],
            'fecha_operacion_hasta' => ['fecha_operacion', '<='],
            'monto_min' => ['monto', '>='],
            'monto_max' => ['monto', '<='],
        ] as $filter => [$column, $operator]) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($column, $operator, $this->filters[$filter]);
            }
        }

        if (array_key_exists('q', $this->filters)) {
            $term = '%'.$this->filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search->where('observaciones', 'like', $term);
            });
        }

        $allowed = ['id', 'tipo_operacion', 'monto', 'fecha_operacion', 'estado', 'created_at', 'updated_at'];
        $sort = in_array($this->filters['sort'] ?? null, $allowed, true)
            ? $this->filters['sort']
            : 'fecha_operacion';
        $direction = ($this->filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', static fn (Builder $ordered): Builder => $ordered->orderBy('id', 'desc'));
    }
}
