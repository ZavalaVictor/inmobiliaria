<?php

namespace App\Actions\ClienteInmuebleIntereses;

use App\Models\Cliente;
use App\Models\ClienteInmuebleInteres;
use App\Models\Inmueble;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateClienteInmuebleInteresAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, Cliente $cliente, array $attributes): ClienteInmuebleInteres
    {
        return DB::transaction(function () use ($user, $cliente, $attributes): ClienteInmuebleInteres {
            $lockedCliente = Cliente::query()
                ->whereKey($cliente->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $inmuebleId = (int) $attributes['inmueble_id'];
            $inmueble = Inmueble::query()->whereKey($inmuebleId)->first();

            if ($inmueble === null) {
                throw ValidationException::withMessages([
                    'inmueble_id' => ['El Inmueble seleccionado no existe o no está disponible.'],
                ]);
            }

            $this->authorizeEnds($user, $lockedCliente, $inmueble);

            $interest = ClienteInmuebleInteres::withTrashed()
                ->where('cliente_id', $lockedCliente->getKey())
                ->where('inmueble_id', $inmueble->getKey())
                ->first();

            if ($interest?->trashed() === false) {
                throw ValidationException::withMessages([
                    'inmueble_id' => ['El Cliente ya tiene un interés activo en este Inmueble.'],
                ]);
            }

            $values = [
                'nivel_interes' => array_key_exists('nivel_interes', $attributes)
                    ? $attributes['nivel_interes']
                    : null,
                'estado' => $attributes['estado'] ?? 'activo',
                'notas' => array_key_exists('notas', $attributes) ? $attributes['notas'] : null,
                'fecha_interes' => now(),
            ];

            if ($interest !== null) {
                $interest->restore();
                $interest->update($values);
            } else {
                $interest = ClienteInmuebleInteres::create([
                    'cliente_id' => $lockedCliente->getKey(),
                    'inmueble_id' => $inmueble->getKey(),
                    ...$values,
                ]);
            }

            return $interest->fresh([
                'inmueble:id,codigo,titulo,slug',
            ]);
        });
    }

    private function authorizeEnds(User $user, Cliente $cliente, Inmueble $inmueble): void
    {
        if (ActorScope::isGlobal($user)) {
            return;
        }

        if (ActorScope::isClient($user)) {
            if ($cliente->user_id !== $user->getKey() || ! $inmueble->publicado) {
                throw ValidationException::withMessages([
                    'inmueble_id' => ['El Inmueble no está disponible para este Cliente.'],
                ]);
            }

            return;
        }

        $agentId = ActorScope::agentId($user);
        $controlsClient = $agentId !== null
            && $cliente->asignacionesAgentes()->where('agente_id', $agentId)->exists();
        $controlsInmueble = $agentId !== null
            && $inmueble->asignacionesAgentes()->where('agente_id', $agentId)->exists();

        if (! $controlsClient || ! $controlsInmueble) {
            throw ValidationException::withMessages([
                'inmueble_id' => ['El Agente no tiene alcance sobre el Cliente y el Inmueble.'],
            ]);
        }
    }
}
