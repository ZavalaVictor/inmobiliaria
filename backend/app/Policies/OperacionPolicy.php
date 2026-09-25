<?php

namespace App\Policies;

use App\Models\Operacion;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class OperacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operaciones.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null);
    }

    public function view(User $user, Operacion $operacion): bool
    {
        if (! $user->can('operaciones.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $operacion->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('operaciones.crear') && ActorScope::isGlobal($user);
    }

    public function update(User $user, Operacion $operacion): bool
    {
        return $user->can('operaciones.actualizar') && ActorScope::isGlobal($user);
    }

    public function delete(User $user, Operacion $operacion): bool
    {
        return $user->can('operaciones.eliminar') && ActorScope::isGlobal($user);
    }
}
