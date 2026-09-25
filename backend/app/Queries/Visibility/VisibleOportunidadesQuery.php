<?php

namespace App\Queries\Visibility;

use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleOportunidadesQuery
{
    public function __construct(private readonly User $user) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->user->can('oportunidades.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $query;
        }

        $agentId = ActorScope::agentId($this->user);

        if ($agentId === null) {
            return $this->deny($query);
        }

        return $query->where(function (Builder $opportunity) use ($agentId): void {
            $opportunity->where('agente_principal_id', $agentId)
                ->orWhere(function (Builder $indirect) use ($agentId): void {
                    $indirect->whereHas('cliente.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId))
                        ->where(function (Builder $property) use ($agentId): void {
                            $property->whereNull('inmueble_id')
                                ->orWhereHas('inmueble.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId));
                        });
                });
        });
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
