<?php

namespace App\Policies;

use App\Models\ClienteAgente;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class ClienteAgentePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('asignaciones_cliente_agente.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null || ActorScope::clientId($user) !== null);
    }

    public function view(User $user, ClienteAgente $asignacion): bool
    {
        if (! $user->can('asignaciones_cliente_agente.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return $asignacion->cliente?->user_id === $user->getKey();
        }

        return ActorScope::agentId($user) === $asignacion->agente_id;
    }

    public function create(User $user): bool
    {
        return $user->can('asignaciones_cliente_agente.crear');
    }

    public function update(User $user, ClienteAgente $asignacion): bool
    {
        return $user->can('asignaciones_cliente_agente.actualizar')
            && (ActorScope::isGlobal($user)
                || ActorScope::agentId($user) === $asignacion->agente_id
                || $asignacion->cliente?->user_id === $user->getKey());
    }

    public function delete(User $user, ClienteAgente $asignacion): bool
    {
        return $user->can('asignaciones_cliente_agente.eliminar')
            && (ActorScope::isGlobal($user)
                || ActorScope::agentId($user) === $asignacion->agente_id
                || $asignacion->cliente?->user_id === $user->getKey());
    }
}
