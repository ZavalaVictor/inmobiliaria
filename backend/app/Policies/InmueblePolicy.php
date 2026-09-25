<?php

namespace App\Policies;

use App\Models\Inmueble;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class InmueblePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inmuebles.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null || ActorScope::clientId($user) !== null);
    }

    public function view(User $user, Inmueble $inmueble): bool
    {
        if (! $user->can('inmuebles.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            $clientId = ActorScope::clientId($user);

            return $clientId !== null
                && $inmueble->interesesClientes()
                    ->where('cliente_id', $clientId)
                    ->whereNull('deleted_at')
                    ->exists();
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $inmueble->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('inmuebles.crear') && ActorScope::isGlobal($user);
    }

    public function update(User $user, Inmueble $inmueble): bool
    {
        if (! $user->can('inmuebles.actualizar')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $inmueble->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }

    public function delete(User $user, Inmueble $inmueble): bool
    {
        return $user->can('inmuebles.eliminar') && ActorScope::isGlobal($user);
    }
}
