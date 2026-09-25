<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;
use App\Support\Authorization\ActorScope;

class DocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documentos.ver') && (ActorScope::isGlobal($user) || ActorScope::agentId($user) !== null);
    }

    public function view(User $user, Documento $documento): bool
    {
        return $user->can('documentos.ver') && $this->canAccessDestination($user, $documento);
    }

    public function create(User $user): bool
    {
        return $user->can('documentos.crear') && ! ActorScope::isClient($user);
    }

    public function update(User $user, Documento $documento): bool
    {
        return $user->can('documentos.actualizar') && $this->canAccessDestination($user, $documento);
    }

    public function delete(User $user, Documento $documento): bool
    {
        return $user->can('documentos.eliminar') && $this->canAccessDestination($user, $documento);
    }

    private function canAccessDestination(User $user, Documento $documento): bool
    {
        $destinations = collect([
            'propietario_id' => $documento->propietario_id,
            'cliente_id' => $documento->cliente_id,
            'inmueble_id' => $documento->inmueble_id,
            'operacion_id' => $documento->operacion_id,
        ])->filter(static fn ($id): bool => $id !== null);

        if ($destinations->count() !== 1) {
            return false;
        }

        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        if ($agentId === null) {
            return false;
        }

        return match ($destinations->keys()->first()) {
            'cliente_id' => $documento->cliente?->asignacionesAgentes()->where('agente_id', $agentId)->exists() ?? false,
            'inmueble_id' => $documento->inmueble?->asignacionesAgentes()->where('agente_id', $agentId)->exists() ?? false,
            'propietario_id' => $documento->propietario?->inmuebles()
                ->whereHas('asignacionesAgentes', static fn ($query) => $query->where('agente_id', $agentId))
                ->exists() ?? false,
            'operacion_id' => $documento->operacion?->asignacionesAgentes()
                ->where('agente_id', $agentId)
                ->exists() ?? false,
            default => false,
        };
    }
}
