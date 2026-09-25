<?php

namespace App\Actions\ClienteAgentes;

use App\Models\Cliente;
use App\Models\ClienteAgente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class RemoveAgenteFromClienteAction
{
    public function execute(Cliente $cliente, ClienteAgente $assignment): void
    {
        DB::transaction(function () use ($cliente, $assignment): void {
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

            $wasPrincipal = (bool) $target->es_principal;
            $target->delete();

            $remaining = ClienteAgente::query()
                ->where('cliente_id', $lockedCliente->getKey())
                ->whereHas('agente');
            $remainingCount = (clone $remaining)->count();

            if ($remainingCount === 0) {
                ClienteAgente::query()
                    ->where('cliente_id', $lockedCliente->getKey())
                    ->update(['es_principal' => false]);

                return;
            }

            $principalCount = (clone $remaining)
                ->where('es_principal', true)
                ->count();

            if (! $wasPrincipal && $principalCount === 1) {
                return;
            }

            $next = (clone $remaining)
                ->orderBy('fecha_asignacion')
                ->orderBy('id')
                ->first();

            ClienteAgente::query()
                ->where('cliente_id', $lockedCliente->getKey())
                ->update(['es_principal' => false]);

            if ($next !== null) {
                ClienteAgente::query()
                    ->whereKey($next->getKey())
                    ->update(['es_principal' => true]);
            }
        });
    }
}
