<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\Propietario;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class DocumentoDestinationAccess
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array{type: string, column: string, id: int, model: Model}
     */
    public function resolve(array $attributes): array
    {
        $destinations = [
            'propietario' => ['column' => 'propietario_id', 'model' => Propietario::class],
            'cliente' => ['column' => 'cliente_id', 'model' => Cliente::class],
            'inmueble' => ['column' => 'inmueble_id', 'model' => Inmueble::class],
            'operacion' => ['column' => 'operacion_id', 'model' => Operacion::class],
        ];

        $selected = collect($destinations)->filter(
            fn (array $destination): bool => ($attributes[$destination['column']] ?? null) !== null
        );

        if ($selected->count() !== 1) {
            throw ValidationException::withMessages([
                'destino' => ['Debe especificarse exactamente un destino.'],
            ]);
        }

        $type = (string) $selected->keys()->first();
        $column = $destinations[$type]['column'];
        $id = (int) $attributes[$column];
        $model = $destinations[$type]['model']::query()->find($id);

        if (! $model instanceof Model) {
            throw ValidationException::withMessages([
                $column => ['El destino seleccionado no existe o no está disponible.'],
            ]);
        }

        return [
            'type' => $type,
            'column' => $column,
            'id' => $id,
            'model' => $model,
        ];
    }

    /**
     * @param  array{type: string, column: string, id: int, model: Model}  $destination
     */
    public function agentCanAccess(User $user, array $destination): bool
    {
        if (ActorScope::isGlobal($user)) {
            return true;
        }

        $agentId = ActorScope::agentId($user);

        if ($agentId === null) {
            return false;
        }

        return match ($destination['type']) {
            'cliente' => $destination['model']->asignacionesAgentes()
                ->where('agente_id', $agentId)
                ->exists(),
            'inmueble' => $destination['model']->asignacionesAgentes()
                ->where('agente_id', $agentId)
                ->exists(),
            'propietario' => $destination['model']->inmuebles()
                ->whereHas('asignacionesAgentes', static fn ($query) => $query->where('agente_id', $agentId))
                ->exists(),
            'operacion' => $destination['model']->asignacionesAgentes()
                ->where('agente_id', $agentId)
                ->exists(),
            default => false,
        };
    }
}
