<?php

namespace App\Queries\Respaldos;

use Illuminate\Database\Eloquent\Builder;

final class RespaldoIndexQuery
{
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach (['estado', 'tipo', 'generado_por_user_id'] as $field) {
            if (array_key_exists($field, $this->filters)) {
                $value = $this->filters[$field];
                if (is_object($value) && property_exists($value, 'value')) {
                    $value = $value->value;
                }
                $query->where($field, $value);
            }
        }

        if (array_key_exists('fecha_desde', $this->filters)) {
            $query->where('fecha_inicio', '>=', $this->filters['fecha_desde']);
        }
        if (array_key_exists('fecha_hasta', $this->filters)) {
            $query->where('fecha_inicio', '<=', $this->filters['fecha_hasta']);
        }

        if (($this->filters['q'] ?? '') !== '') {
            $term = '%'.$this->filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search->where('nombre_archivo', 'like', $term);
            });
        }

        $allowed = [
            'id',
            'tipo',
            'estado',
            'tamano_bytes',
            'fecha_inicio',
            'fecha_finalizacion',
            'created_at',
            'updated_at',
        ];
        $sort = in_array($this->filters['sort'] ?? null, $allowed, true)
            ? $this->filters['sort']
            : 'fecha_inicio';
        $direction = ($this->filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', $direction);
    }
}
