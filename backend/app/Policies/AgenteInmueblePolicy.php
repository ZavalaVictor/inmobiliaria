<?php

namespace App\Policies;

use App\Models\AgenteInmueble;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class AgenteInmueblePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('asignaciones_agente_inmueble.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null);
    }

    public function view(User $user, AgenteInmueble $asignacion): bool
    {
        if (! $user->can('asignaciones_agente_inmueble.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        return ActorScope::agentId($user) === $asignacion->agente_id;
    }

    public function create(User $user): bool
    {
        return $user->can('asignaciones_agente_inmueble.crear');
    }

    public function update(User $user, AgenteInmueble $asignacion): bool
    {
        return $user->can('asignaciones_agente_inmueble.actualizar')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) === $asignacion->agente_id);
    }

    public function delete(User $user, AgenteInmueble $asignacion): bool
    {
        return $user->can('asignaciones_agente_inmueble.eliminar')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) === $asignacion->agente_id);
    }
}
