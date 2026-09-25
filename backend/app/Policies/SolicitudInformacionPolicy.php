<?php

namespace App\Policies;

use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class SolicitudInformacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('solicitudes.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null || ActorScope::clientId($user) !== null);
    }

    public function view(User $user, SolicitudInformacion $solicitud): bool
    {
        if (! $user->can('solicitudes.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return $solicitud->cliente?->user_id === $user->getKey();
        }

        return $this->agentCanAccess($user, $solicitud);
    }

    public function create(User $user): bool
    {
        return $user->can('solicitudes.crear');
    }

    public function update(User $user, SolicitudInformacion $solicitud): bool
    {
        if (! $user->can('solicitudes.actualizar')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            return false;
        }

        return $this->agentCanAccess($user, $solicitud);
    }

    public function delete(User $user, SolicitudInformacion $solicitud): bool
    {
        return $user->can('solicitudes.eliminar') && ActorScope::isGlobal($user);
    }

    private function agentCanAccess(User $user, SolicitudInformacion $solicitud): bool
    {
        $agentId = ActorScope::agentId($user);

        if ($agentId === null) {
            return false;
        }

        return ($solicitud->cliente_id !== null
                && $solicitud->cliente?->asignacionesAgentes()->where('agente_id', $agentId)->exists())
            || ($solicitud->inmueble_id !== null
                && $solicitud->inmueble?->asignacionesAgentes()->where('agente_id', $agentId)->exists())
            || $solicitud->atendida_por_user_id === $user->getKey();
    }
}
