<?php

namespace App\Policies;

use App\Models\ClienteInmuebleInteres;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class ClienteInmuebleInteresPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('intereses.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null || ActorScope::clientId($user) !== null);
    }

    public function view(User $user, ClienteInmuebleInteres $interes): bool
    {
        if (! $user->can('intereses.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return $interes->cliente?->user_id === $user->getKey();
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && ($interes->cliente?->asignacionesAgentes()->where('agente_id', $agentId)->exists()
                || $interes->inmueble?->asignacionesAgentes()->where('agente_id', $agentId)->exists());
    }

    public function create(User $user): bool
    {
        return $user->can('intereses.crear')
            && ! (ActorScope::isAgent($user) && ActorScope::agentId($user) === null);
    }

    public function update(User $user, ClienteInmuebleInteres $interes): bool
    {
        if (! $user->can('intereses.actualizar')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return $interes->cliente?->user_id === $user->getKey();
        }

        return $this->agentControlsBothEnds($user, $interes);
    }

    public function delete(User $user, ClienteInmuebleInteres $interes): bool
    {
        if (! $user->can('intereses.eliminar')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return $interes->cliente?->user_id === $user->getKey();
        }

        return $this->agentControlsBothEnds($user, $interes);
    }

    private function agentControlsBothEnds(User $user, ClienteInmuebleInteres $interes): bool
    {
        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $interes->cliente?->asignacionesAgentes()->where('agente_id', $agentId)->exists()
            && $interes->inmueble?->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }
}
