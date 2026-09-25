<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clientes.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null || ActorScope::clientId($user) !== null);
    }

    public function view(User $user, Cliente $cliente): bool
    {
        if (! $user->can('clientes.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return $cliente->user_id === $user->getKey();
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $cliente->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('clientes.crear') && ! ActorScope::isClient($user);
    }

    public function update(User $user, Cliente $cliente): bool
    {
        if (! $user->can('clientes.actualizar')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return $cliente->user_id === $user->getKey();
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $cliente->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        if (! $user->can('clientes.eliminar') || ActorScope::isClient($user)) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $cliente->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }
}
