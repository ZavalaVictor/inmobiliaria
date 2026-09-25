<?php

namespace App\Policies;

use App\Models\Propietario;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class PropietarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('propietarios.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null);
    }

    public function view(User $user, Propietario $propietario): bool
    {
        if (! $user->can('propietarios.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $propietario->inmuebles()
                ->whereHas('asignacionesAgentes', static fn ($query) => $query->where('agente_id', $agentId))
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('propietarios.crear') && ActorScope::isGlobal($user);
    }

    public function update(User $user, Propietario $propietario): bool
    {
        return $user->can('propietarios.actualizar') && ActorScope::isGlobal($user);
    }

    public function delete(User $user, Propietario $propietario): bool
    {
        return $user->can('propietarios.eliminar') && ActorScope::isGlobal($user);
    }
}
