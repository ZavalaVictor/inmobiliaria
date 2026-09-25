<?php

namespace App\Policies;

use App\Models\Agente;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class AgentePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('agentes.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null);
    }

    public function view(User $user, Agente $agente): bool
    {
        if (! $user->can('agentes.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        return ActorScope::isAgent($user) && $agente->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->can('agentes.crear') && ActorScope::isGlobal($user);
    }

    public function update(User $user, Agente $agente): bool
    {
        return $user->can('agentes.actualizar') && ActorScope::isGlobal($user);
    }

    public function delete(User $user, Agente $agente): bool
    {
        return $user->can('agentes.eliminar') && ActorScope::isGlobal($user);
    }
}
