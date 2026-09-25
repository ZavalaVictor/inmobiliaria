<?php

namespace App\Actions\Clientes;

use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class CreateClienteAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, array $attributes): Cliente
    {
        return DB::transaction(function () use ($user, $attributes): Cliente {
            $agent = null;

            if (! ActorScope::isGlobal($user)) {
                if (! ActorScope::isAgent($user) || $user->agente === null) {
                    throw new AuthorizationException('El usuario no tiene un alcance válido para crear clientes.');
                }

                $agent = $user->agente;
            }

            $cliente = Cliente::create(Arr::only($attributes, [
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'email',
                'telefono',
                'tipo_interes',
                'presupuesto_min',
                'presupuesto_max',
                'preferencias',
                'estado_cliente',
            ]));

            if ($agent !== null) {
                ClienteAgente::create([
                    'cliente_id' => $cliente->id,
                    'agente_id' => $agent->id,
                    'es_principal' => true,
                    'fecha_asignacion' => now(),
                ]);
            }

            return $cliente->fresh(['user']);
        });
    }
}
