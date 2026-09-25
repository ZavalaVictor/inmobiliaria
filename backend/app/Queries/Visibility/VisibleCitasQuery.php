<?php

namespace App\Queries\Visibility;

use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleCitasQuery
{
    public function __construct(private readonly User $user) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->user->can('citas.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $query;
        }

        if (ActorScope::isClient($this->user)) {
            return $query->whereHas('cliente', fn (Builder $client): Builder => $client->where('user_id', $this->user->getKey()));
        }

        $agentId = ActorScope::agentId($this->user);

        return $agentId === null
            ? $this->deny($query)
            : $query->where('agente_id', $agentId);
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
