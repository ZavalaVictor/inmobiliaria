<?php

namespace App\Queries\Visibility;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleInteraccionesClienteQuery
{
    public function __construct(
        private readonly User $user,
        private readonly Cliente $cliente,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters = []): Builder
    {
        $query->where('interacciones_cliente.cliente_id', $this->cliente->getKey());

        if (! $this->user->can('interacciones.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $this->filter($query, $filters);
        }

        $agentId = ActorScope::agentId($this->user);
        if ($agentId === null) {
            return $this->deny($query);
        }

        $query->whereHas('cliente.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId));

        return $this->filter($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filter(Builder $query, array $filters): Builder
    {
        if (array_key_exists('tipo', $filters)) {
            $query->where('tipo', $filters['tipo']);
        }

        if (array_key_exists('resultado', $filters)) {
            $query->where('resultado', $filters['resultado']);
        }

        if (array_key_exists('registrado_por_user_id', $filters)) {
            $query->where('registrado_por_user_id', $filters['registrado_por_user_id']);
        }

        foreach ([
            'fecha_interaccion_desde' => 'fecha_interaccion',
            'fecha_interaccion_hasta' => 'fecha_interaccion',
            'fecha_proxima_accion_desde' => 'fecha_proxima_accion',
            'fecha_proxima_accion_hasta' => 'fecha_proxima_accion',
        ] as $filter => $column) {
            if (! array_key_exists($filter, $filters)) {
                continue;
            }

            $operator = str_ends_with($filter, '_desde') ? '>=' : '<=';
            $query->where($column, $operator, $filters[$filter]);
        }

        if (array_key_exists('q', $filters)) {
            $term = '%'.$filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search
                    ->where('descripcion', 'like', $term)
                    ->orWhere('resultado', 'like', $term)
                    ->orWhere('proxima_accion', 'like', $term);
            });
        }

        $allowedSorts = [
            'id',
            'tipo',
            'fecha_interaccion',
            'fecha_proxima_accion',
            'created_at',
            'updated_at',
        ];
        $sort = in_array($filters['sort'] ?? null, $allowedSorts, true)
            ? $filters['sort']
            : 'fecha_interaccion';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', static fn (Builder $ordered) => $ordered->orderByDesc('id'));
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
