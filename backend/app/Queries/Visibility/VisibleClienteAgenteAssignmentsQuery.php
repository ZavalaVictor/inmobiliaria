<?php

namespace App\Queries\Visibility;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleClienteAgenteAssignmentsQuery
{
    public function __construct(
        private readonly User $user,
        private readonly Cliente $cliente,
    ) {}

    public function apply(Builder $query): Builder
    {
        $query
            ->where('cliente_agente.cliente_id', $this->cliente->getKey())
            ->whereHas('agente');

        if (! $this->user->can('asignaciones_cliente_agente.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $query;
        }

        $agentId = ActorScope::agentId($this->user);
        if ($agentId !== null) {
            return $query->where('cliente_agente.agente_id', $agentId);
        }

        if (ActorScope::isClient($this->user)
            && $this->cliente->user_id === $this->user->getKey()
            && ActorScope::clientId($this->user) !== null) {
            return $query;
        }

        return $this->deny($query);
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
