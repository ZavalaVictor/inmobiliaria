<?php

namespace App\Queries\Visibility;

use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;

final class VisibleHistorialCorreosQuery
{
    public function __construct(private readonly User $user) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->user->can('historial_correos.ver')) {
            return $this->deny($query);
        }

        if (ActorScope::isGlobal($this->user)) {
            return $query;
        }

        $agentId = ActorScope::agentId($this->user);

        if ($agentId === null) {
            return $this->deny($query);
        }

        return $query->where(function (Builder $email) use ($agentId): void {
            $email->whereHas('cliente.asignacionesAgentes', static fn (Builder $assignment) => $assignment->where('agente_id', $agentId))
                ->orWhereHas('cita', static fn (Builder $appointment) => $appointment->where('agente_id', $agentId))
                ->orWhere('destinatario_user_id', $this->user->getKey())
                ->orWhere('enviado_por_user_id', $this->user->getKey());
        });
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
