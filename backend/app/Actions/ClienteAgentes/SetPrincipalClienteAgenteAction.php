<?php

namespace App\Actions\ClienteAgentes;

use App\Models\Cliente;
use App\Models\ClienteAgente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class SetPrincipalClienteAgenteAction
{
    public function execute(Cliente $cliente, ClienteAgente $assignment): ClienteAgente
    {
        return DB::transaction(function () use ($cliente, $assignment): ClienteAgente {
            $lockedCliente = Cliente::query()
                ->whereKey($cliente->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $target = ClienteAgente::query()
                ->whereKey($assignment->getKey())
                ->where('cliente_id', $lockedCliente->getKey())
                ->whereHas('agente')
                ->lockForUpdate()
                ->first();

            if ($target === null) {
                throw (new ModelNotFoundException)->setModel(ClienteAgente::class, [$assignment->getKey()]);
            }

            ClienteAgente::query()
                ->where('cliente_id', $lockedCliente->getKey())
                ->update(['es_principal' => false]);

            ClienteAgente::query()
                ->whereKey($target->getKey())
                ->update(['es_principal' => true]);

            return $target->fresh([
                'agente:id,user_id,numero_empleado',
                'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
            ]);
        });
    }
}
