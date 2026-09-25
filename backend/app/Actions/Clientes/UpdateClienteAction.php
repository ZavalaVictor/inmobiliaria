<?php

namespace App\Actions\Clientes;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Support\Arr;

final class UpdateClienteAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, Cliente $cliente, array $attributes): Cliente
    {
        $allowed = [
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'telefono',
            'tipo_interes',
            'presupuesto_min',
            'presupuesto_max',
            'preferencias',
        ];

        if (! ActorScope::isClient($user)) {
            $allowed[] = 'email';
            $allowed[] = 'estado_cliente';
        }

        $cliente->fill(Arr::only($attributes, $allowed));
        $cliente->save();

        return $cliente->fresh(['user']);
    }
}
