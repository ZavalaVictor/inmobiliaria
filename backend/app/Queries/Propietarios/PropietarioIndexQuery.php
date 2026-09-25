<?php

namespace App\Queries\Propietarios;

use Illuminate\Database\Eloquent\Builder;

final class PropietarioIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);
        $this->applyEnumFilters($query);

        $sort = $this->filters['sort'] ?? 'created_at';
        $direction = $this->filters['direction'] ?? 'desc';

        return $query->orderBy($sort, $direction);
    }

    private function applySearch(Builder $query): void
    {
        $search = $this->filters['q'] ?? null;

        if ($search === null || $search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            $builder->where('nombre_razon_social', 'like', "%{$search}%")
                ->orWhere('rfc', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('telefono', 'like', "%{$search}%");
        });
    }

    private function applyEnumFilters(Builder $query): void
    {
        foreach (['tipo_persona', 'estado_registro'] as $column) {
            if (array_key_exists($column, $this->filters)) {
                $query->where($column, $this->filters[$column]);
            }
        }
    }
}
