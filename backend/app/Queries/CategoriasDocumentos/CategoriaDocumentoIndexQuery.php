<?php

namespace App\Queries\CategoriasDocumentos;

use Illuminate\Database\Eloquent\Builder;

final class CategoriaDocumentoIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        if (array_key_exists('activo', $this->filters)) {
            $query->where('activo', $this->filters['activo']);
        }

        $search = $this->filters['q'] ?? null;

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $sort = $this->filters['sort'] ?? 'nombre';
        $direction = $this->filters['direction'] ?? 'asc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction);
    }
}
