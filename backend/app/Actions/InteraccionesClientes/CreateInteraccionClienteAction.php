<?php

namespace App\Actions\InteraccionesClientes;

use App\Models\Cliente;
use App\Models\InteraccionCliente;
use App\Models\User;

final class CreateInteraccionClienteAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, Cliente $cliente, array $attributes): InteraccionCliente
    {
        $values = array_intersect_key($attributes, array_flip([
            'tipo',
            'descripcion',
            'resultado',
            'fecha_interaccion',
            'proxima_accion',
            'fecha_proxima_accion',
        ]));

        $values['cliente_id'] = $cliente->getKey();
        $values['registrado_por_user_id'] = $user->getKey();

        return InteraccionCliente::create($values)->fresh([
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'registradoPor:id,nombres,apellido_paterno,apellido_materno',
        ]);
    }
}
