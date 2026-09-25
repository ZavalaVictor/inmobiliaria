<?php

namespace App\Actions\InteraccionesClientes;

use App\Models\InteraccionCliente;

final class UpdateInteraccionClienteAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(InteraccionCliente $interaccion, array $attributes): InteraccionCliente
    {
        $interaccion->update(array_intersect_key($attributes, array_flip([
            'tipo',
            'descripcion',
            'resultado',
            'proxima_accion',
            'fecha_proxima_accion',
        ])));

        return $interaccion->fresh([
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'registradoPor:id,nombres,apellido_paterno,apellido_materno',
        ]);
    }
}
