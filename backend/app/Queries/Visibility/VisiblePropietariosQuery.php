<?php

namespace App\Queries\Visibility;

use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisiblePropietariosQuery
{
    public function __construct(private readonly User $user) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->user->can('propietarios.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $query;
        }

        $agentId = ActorScope::agentId($this->user);

        return $agentId === null
            ? $this->deny($query)
            : $query->whereHas('inmuebles.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId));
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
