<?php

namespace App\Policies;

use App\Models\InmuebleImagen;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class InmuebleImagenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('imagenes_inmuebles.ver')
            && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null || ActorScope::clientId($user) !== null);
    }

    public function view(User $user, InmuebleImagen $imagen): bool
    {
        return $user->can('imagenes_inmuebles.ver') && $this->canAccessProperty($user, $imagen);
    }

    public function create(User $user): bool
    {
        return $user->can('imagenes_inmuebles.crear');
    }

    public function update(User $user, InmuebleImagen $imagen): bool
    {
        return $user->can('imagenes_inmuebles.actualizar') && $this->canAccessProperty($user, $imagen);
    }

    public function delete(User $user, InmuebleImagen $imagen): bool
    {
        return $user->can('imagenes_inmuebles.eliminar') && $this->canAccessProperty($user, $imagen);
    }

    private function canAccessProperty(User $user, InmuebleImagen $imagen): bool
    {
        if (ActorScope::isGlobal($user)) {
            return true;
        }

        if (ActorScope::isClient($user)) {
            $clientId = ActorScope::clientId($user);

            return $clientId !== null
                && $imagen->inmueble->interesesClientes()
                    ->where('cliente_id', $clientId)
                    ->whereNull('deleted_at')
                    ->exists();
        }

        $agentId = ActorScope::agentId($user);

        return $agentId !== null
            && $imagen->inmueble->asignacionesAgentes()->where('agente_id', $agentId)->exists();
    }
}
