<?php

namespace App\Queries\Solicitudes;

use Illuminate\Database\Eloquent\Builder;

final class SolicitudIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach ([
            'estado',
            'medio_preferido',
            'origen',
            'cliente_id',
            'inmueble_id',
            'atendida_por_user_id',
        ] as $filter) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($filter, $this->filters[$filter]);
            }
        }

        foreach ([
            'fecha_solicitud_desde' => ['fecha_solicitud', '>='],
            'fecha_solicitud_hasta' => ['fecha_solicitud', '<='],
            'fecha_atencion_desde' => ['fecha_atencion', '>='],
            'fecha_atencion_hasta' => ['fecha_atencion', '<='],
        ] as $filter => [$column, $operator]) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($column, $operator, $this->filters[$filter]);
            }
        }

        if (array_key_exists('q', $this->filters)) {
            $term = '%'.$this->filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search
                    ->where('nombre', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('telefono', 'like', $term)
                    ->orWhere('mensaje', 'like', $term);
            });
        }

        $allowedSorts = [
            'id',
            'nombre',
            'email',
            'estado',
            'origen',
            'fecha_solicitud',
            'fecha_atencion',
            'created_at',
            'updated_at',
        ];
        $sort = in_array($this->filters['sort'] ?? null, $allowedSorts, true)
            ? $this->filters['sort']
            : 'fecha_solicitud';
        $direction = ($this->filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', static fn (Builder $ordered) => $ordered->orderByDesc('id'));
    }
}
