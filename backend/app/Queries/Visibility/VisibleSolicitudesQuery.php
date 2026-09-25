<?php

namespace App\Queries\Visibility;

use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleSolicitudesQuery
{
    public function __construct(private readonly User $user) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->user->can('solicitudes.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $query;
        }

        if (ActorScope::isClient($this->user)) {
            return $query->whereHas('cliente', fn (Builder $client): Builder => $client->where('user_id', $this->user->getKey()));
        }

        $agentId = ActorScope::agentId($this->user);

        if ($agentId === null) {
            return $this->deny($query);
        }

        return $query->where(function (Builder $request) use ($agentId): void {
            $request->whereHas('cliente.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId))
                ->orWhereHas('inmueble.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId))
                ->orWhere('atendida_por_user_id', $this->user->getKey());
        });
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
