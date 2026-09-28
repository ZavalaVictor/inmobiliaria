<?php

namespace App\Queries\Oportunidades;

use Illuminate\Database\Eloquent\Builder;

final class OportunidadIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach ([
            'etapa',
            'estado',
            'cliente_id',
            'inmueble_id',
            'agente_principal_id',
            'solicitud_informacion_id',
        ] as $filter) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($filter, $this->filters[$filter]);
            }
        }

        foreach ([
            'fecha_apertura_desde' => ['fecha_apertura', '>='],
            'fecha_apertura_hasta' => ['fecha_apertura', '<='],
            'fecha_cierre_desde' => ['fecha_cierre', '>='],
            'fecha_cierre_hasta' => ['fecha_cierre', '<='],
        ] as $filter => [$column, $operator]) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($column, $operator, $this->filters[$filter]);
            }
        }

        if (array_key_exists('q', $this->filters)) {
            $term = '%'.$this->filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search
                    ->where('titulo', 'like', $term)
                    ->orWhere('notas', 'like', $term)
                    ->orWhere('motivo_perdida', 'like', $term);
            });
        }

        $allowedSorts = [
            'id',
            'titulo',
            'etapa',
            'estado',
            'fecha_apertura',
            'fecha_cierre',
            'created_at',
            'updated_at',
        ];
        $sort = in_array($this->filters['sort'] ?? null, $allowedSorts, true)
            ? $this->filters['sort']
            : 'fecha_apertura';
        $direction = ($this->filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', static fn (Builder $ordered) => $ordered->orderByDesc('id'));
    }
}
