<?php

namespace App\Policies;

use App\Models\Cita;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class CitaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('citas.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null || ActorScope::clientId($user) !== null);
    }

    public function view(User $user, Cita $cita): bool
    {
        if (! $user->can('citas.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return $cita->cliente?->user_id === $user->getKey();
        }

        return ActorScope::agentId($user) === $cita->agente_id;
    }

    public function create(User $user): bool
    {
        return $user->can('citas.crear');
    }

    public function update(User $user, Cita $cita): bool
    {
        if (! $user->can('citas.actualizar')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        return ActorScope::agentId($user) === $cita->agente_id;
    }

    public function delete(User $user, Cita $cita): bool
    {
        return $user->can('citas.eliminar') && ActorScope::isGlobal($user);
    }
}
