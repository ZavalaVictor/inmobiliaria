<?php

namespace App\Queries\Visibility;

use App\Models\Inmueble;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleAgenteInmuebleAssignmentsQuery
{
    public function __construct(
        private readonly User $user,
        private readonly Inmueble $inmueble,
    ) {}

    public function apply(Builder $query): Builder
    {
        $query
            ->where('agente_inmueble.inmueble_id', $this->inmueble->getKey())
            ->whereHas('agente');

        if (! $this->user->can('asignaciones_agente_inmueble.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $query;
        }

        $agentId = ActorScope::agentId($this->user);

        return $agentId === null
            ? $this->deny($query)
            : $query->where('agente_inmueble.agente_id', $agentId);
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
