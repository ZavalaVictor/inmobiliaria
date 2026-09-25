<?php

namespace App\Queries\Visibility;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleClienteInmuebleInteresesQuery
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
        $query
            ->where('cliente_inmueble_intereses.cliente_id', $this->cliente->getKey())
            ->whereHas('inmueble');

        if (! $this->user->can('intereses.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $this->filter($query, $filters);
        }

        $agentId = ActorScope::agentId($this->user);
        if ($agentId !== null) {
            $query->where(function (Builder $scope) use ($agentId): void {
                $scope
                    ->whereHas('cliente.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId))
                    ->orWhereHas('inmueble.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId));
            });

            return $this->filter($query, $filters);
        }

        if (ActorScope::isClient($this->user)
            && $this->cliente->user_id === $this->user->getKey()
            && ActorScope::clientId($this->user) !== null) {
            return $this->filter($query, $filters);
        }

        return $this->deny($query);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filter(Builder $query, array $filters): Builder
    {
        if (array_key_exists('nivel_interes', $filters)) {
            $query->where('nivel_interes', $filters['nivel_interes']);
        }

        if (array_key_exists('estado', $filters)) {
            $query->where('estado', $filters['estado']);
        }

        if (array_key_exists('inmueble_id', $filters)) {
            $query->where('inmueble_id', $filters['inmueble_id']);
        }

        $sort = $filters['sort'] ?? 'fecha_interes';
        $direction = $filters['direction'] ?? 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->when($sort !== 'id', static fn (Builder $ordered) => $ordered->orderByDesc('id'));
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
