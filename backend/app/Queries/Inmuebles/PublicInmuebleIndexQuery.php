<?php

namespace App\Queries\Inmuebles;

use Illuminate\Database\Eloquent\Builder;

final class PublicInmuebleIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): Builder
    {
        $query->where('publicado', true);

        $this->applySearch($query, $filters['q'] ?? null);
        $this->applyLocation($query, $filters['ubicacion'] ?? null);
        $this->applyPropertyType($query, $filters['tipo_inmueble'] ?? null);

        foreach (['tipo_operacion', 'categoria_id', 'municipio', 'estado_ubicacion'] as $column) {
            if (array_key_exists($column, $filters)) {
                $query->where($column, $filters[$column]);
            }
        }

        $this->applyPriceRange($query, $filters);

        return $query
            ->with([
                'categoria:id,nombre',
                'imagenPrincipal:id,inmueble_id,url_publica,nombre_original,mime_type,es_principal,orden,texto_alternativo',
            ])
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');
    }

    private function applyLocation(Builder $query, mixed $location): void
    {
        if (! is_string($location) || trim($location) === '') {
            return;
        }

        $term = '%'.trim($location).'%';

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('colonia', 'like', $term)
                ->orWhere('municipio', 'like', $term)
                ->orWhere('estado_ubicacion', 'like', $term);
        });
    }

    private function applyPropertyType(Builder $query, mixed $propertyType): void
    {
        if (! is_string($propertyType) || trim($propertyType) === '') {
            return;
        }

        $query->whereHas('categoria', function (Builder $builder) use ($propertyType): void {
            $builder->where('nombre', 'like', '%'.trim($propertyType).'%');
        });
    }

    /** @param array<string, mixed> $filters */
    private function applyPriceRange(Builder $query, array $filters): void
    {
        $minimum = $filters['precio_min'] ?? null;
        $maximum = $filters['precio_max'] ?? null;

        if ($minimum === null && $maximum === null) {
            return;
        }

        $query->where(function (Builder $builder) use ($filters, $minimum, $maximum): void {
            $apply = function (Builder $priceQuery, string $column) use ($minimum, $maximum): void {
                if ($minimum !== null) {
                    $priceQuery->where($column, '>=', $minimum);
                }

                if ($maximum !== null) {
                    $priceQuery->where($column, '<=', $maximum);
                }
            };

            if (($filters['tipo_operacion'] ?? null) === 'venta') {
                $apply($builder, 'precio_venta');
                return;
            }

            if (($filters['tipo_operacion'] ?? null) === 'renta') {
                $apply($builder, 'renta_mensual');
                return;
            }

            $builder->where(function (Builder $sale) use ($apply): void {
                $apply($sale, 'precio_venta');
            })->orWhere(function (Builder $rent) use ($apply): void {
                $apply($rent, 'renta_mensual');
            });
        });
    }

    private function applySearch(Builder $query, mixed $search): void
    {
        if (! is_string($search) || $search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            $term = "%{$search}%";

            $builder->where('codigo', 'like', $term)
                ->orWhere('titulo', 'like', $term)
                ->orWhere('descripcion', 'like', $term)
                ->orWhere('colonia', 'like', $term)
                ->orWhere('municipio', 'like', $term)
                ->orWhere('estado_ubicacion', 'like', $term);
        });
    }
}
