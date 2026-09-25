<?php

namespace App\Policies;

use App\Models\InteraccionCliente;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class InteraccionClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('interacciones.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null);
    }

    public function view(User $user, InteraccionCliente $interaccion): bool
    {
        if (! $user->can('interacciones.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $interaccion->cliente?->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('interacciones.crear') && ! ActorScope::isClient($user);
    }

    public function update(User $user, InteraccionCliente $interaccion): bool
    {
        return $user->can('interacciones.actualizar') && $this->canAccess($user, $interaccion);
    }

    public function delete(User $user, InteraccionCliente $interaccion): bool
    {
        return $user->can('interacciones.eliminar') && $this->canAccess($user, $interaccion);
    }

    private function canAccess(User $user, InteraccionCliente $interaccion): bool
    {
        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $interaccion->cliente?->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }
}
