<?php

namespace App\Queries\Categorias;

use Illuminate\Database\Eloquent\Builder;

final class CategoriaIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);

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
            $builder->where('nombre', 'like', "%{$search}%")
                ->orWhere('descripcion', 'like', "%{$search}%");
        });
    }
}
