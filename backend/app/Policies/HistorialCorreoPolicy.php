<?php

namespace App\Policies;

use App\Models\HistorialCorreo;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class HistorialCorreoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('historial_correos.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null);
    }

    public function view(User $user, HistorialCorreo $correo): bool
    {
        if (! $user->can('historial_correos.ver')) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        if ($agentId === null) {
            return false;
        }

        return ($correo->cliente?->asignacionesAgentes()->where('agente_id', $agentId)->exists() ?? false)
            || $correo->cita?->agente_id === $agentId
            || $correo->destinatario_user_id === $user->getKey()
            || $correo->enviado_por_user_id === $user->getKey();
    }
}
