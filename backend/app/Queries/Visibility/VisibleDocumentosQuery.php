<?php

namespace App\Queries\Visibility;

use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleDocumentosQuery
{
    public function __construct(private readonly User $user) {}

    public function apply(Builder $query): Builder
    {
        $query = $query
            ->whereRaw('((propietario_id IS NOT NULL) + (cliente_id IS NOT NULL) + (inmueble_id IS NOT NULL) + (operacion_id IS NOT NULL)) = 1');

        if (! $this->user->can('documentos.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $query;
        }

        $agentId = ActorScope::agentId($this->user);

        if ($agentId === null) {
            return $this->deny($query);
        }

        return $query->where(function (Builder $document) use ($agentId): void {
            $document->whereHas('cliente.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId))
                ->orWhereHas('inmueble.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId))
                ->orWhereHas('propietario.inmuebles.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId))
                ->orWhereHas('operacion.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId));
        });
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
