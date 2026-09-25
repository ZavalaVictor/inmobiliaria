<?php

namespace App\Queries\Inmuebles;

use Illuminate\Database\Eloquent\Builder;

final class InmuebleIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $filters,
        private readonly bool $includeOwner,
    ) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);
        $this->applyFilters($query);

        $sort = $this->filters['sort'] ?? 'created_at';
        $direction = $this->filters['direction'] ?? 'desc';
        $relations = ['categoria:id,nombre'];

        if ($this->includeOwner) {
            $relations[] = 'propietario:id,nombre_razon_social';
        }

        return $query->with($relations)->orderBy($sort, $direction);
    }

    private function applySearch(Builder $query): void
    {
        $search = $this->filters['q'] ?? null;

        if ($search === null || $search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            $term = "%{$search}%";

            $builder->where('codigo', 'like', $term)
                ->orWhere('titulo', 'like', $term)
                ->orWhere('slug', 'like', $term)
                ->orWhere('descripcion', 'like', $term)
                ->orWhere('calle', 'like', $term)
                ->orWhere('numero_exterior', 'like', $term)
                ->orWhere('colonia', 'like', $term)
                ->orWhere('municipio', 'like', $term)
                ->orWhere('estado_ubicacion', 'like', $term)
                ->orWhere('codigo_postal', 'like', $term)
                ->orWhere('referencias', 'like', $term);
        });
    }

    private function applyFilters(Builder $query): void
    {
        foreach ([
            'tipo_operacion',
            'estado_disponibilidad',
            'categoria_id',
            'propietario_id',
            'habitaciones',
            'banos_completos',
            'publicado',
            'municipio',
            'estado_ubicacion',
        ] as $column) {
            if (array_key_exists($column, $this->filters)) {
                $query->where($column, $this->filters[$column]);
            }
        }
    }
}
