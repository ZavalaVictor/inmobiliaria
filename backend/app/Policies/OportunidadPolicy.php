<?php

namespace App\Policies;

use App\Models\Oportunidad;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class OportunidadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('oportunidades.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null);
    }

    public function view(User $user, Oportunidad $oportunidad): bool
    {
        if (! $user->can('oportunidades.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        return $this->agentCanView($user, $oportunidad);
    }

    public function create(User $user): bool
    {
        return $user->can('oportunidades.crear');
    }

    public function update(User $user, Oportunidad $oportunidad): bool
    {
        return $user->can('oportunidades.actualizar')
            && (ActorScope::isGlobal($user)
                || (ActorScope::agentId($user) !== null
                    && $oportunidad->agente_principal_id === ActorScope::agentId($user)));
    }

    public function delete(User $user, Oportunidad $oportunidad): bool
    {
        return $user->can('oportunidades.eliminar')
            && (ActorScope::isGlobal($user)
                || (ActorScope::agentId($user) !== null
                    && $oportunidad->agente_principal_id === ActorScope::agentId($user)));
    }

    private function agentCanView(User $user, Oportunidad $oportunidad): bool
    {
        $agentId = ActorScope::agentId($user);

        if ($agentId === null) {
            return false;
        }

        if ($oportunidad->agente_principal_id === $agentId) {
            return true;
        }

        $clientAssigned = $oportunidad->cliente?->asignacionesAgentes()
            ->where('agente_id', $agentId)
            ->exists();

        if (! $clientAssigned) {
            return false;
        }

        return $oportunidad->inmueble_id === null
            || $oportunidad->inmueble?->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }
}
