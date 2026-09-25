<?php

namespace App\Queries\Clientes;

use Illuminate\Database\Eloquent\Builder;

final class ClienteIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);
        $this->applyEnumFilters($query);
        $this->applyAgentFilter($query);

        $sort = $this->filters['sort'] ?? 'created_at';
        $direction = $this->filters['direction'] ?? 'desc';

        return $query
            ->with(['user:id,nombres,apellido_paterno,apellido_materno,email'])
            ->orderBy($sort, $direction);
    }

    private function applySearch(Builder $query): void
    {
        $search = $this->filters['q'] ?? null;

        if ($search === null || $search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            $builder->where('nombres', 'like', "%{$search}%")
                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                ->orWhere('apellido_materno', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('telefono', 'like', "%{$search}%");
        });
    }

    private function applyEnumFilters(Builder $query): void
    {
        foreach (['estado_cliente', 'tipo_interes'] as $column) {
            if (array_key_exists($column, $this->filters)) {
                $query->where($column, $this->filters[$column]);
            }
        }
    }

    private function applyAgentFilter(Builder $query): void
    {
        if (! array_key_exists('agente_id', $this->filters)) {
            return;
        }

        $query->whereHas(
            'asignacionesAgentes',
            fn (Builder $assignment): Builder => $assignment->where('agente_id', $this->filters['agente_id'])
        );
    }
}
