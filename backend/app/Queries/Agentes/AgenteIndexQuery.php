<?php

namespace App\Queries\Agentes;

use Illuminate\Database\Eloquent\Builder;

final class AgenteIndexQuery
{
    private const SORTABLE_COLUMNS = [
        'id',
        'numero_empleado',
        'estado_laboral',
        'fecha_contratacion',
        'created_at',
        'updated_at',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);

        if (array_key_exists('estado_laboral', $this->filters)) {
            $query->where('estado_laboral', $this->filters['estado_laboral']);
        }

        $sort = $this->filters['sort'] ?? 'created_at';
        $direction = $this->filters['direction'] ?? 'desc';

        return $query
            ->orderBy(in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'created_at', $direction === 'asc' ? 'asc' : 'desc');
    }

    private function applySearch(Builder $query): void
    {
        $search = $this->filters['q'] ?? null;

        if ($search === null || $search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            $term = "%{$search}%";

            $builder->where('numero_empleado', 'like', $term)
                ->orWhere('telefono_corporativo', 'like', $term)
                ->orWhere('zona_asignacion', 'like', $term)
                ->orWhereHas('user', function (Builder $userQuery) use ($term): void {
                    $userQuery->where('nombres', 'like', $term)
                        ->orWhere('apellido_paterno', 'like', $term)
                        ->orWhere('apellido_materno', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('telefono', 'like', $term);
                });
        });
    }
}
