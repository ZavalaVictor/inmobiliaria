<?php

namespace App\Actions\ClienteAgentes;

use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class RemoveAgenteFromClienteAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(Cliente $cliente, ClienteAgente $assignment, ?User $actor = null): void
    {
        DB::transaction(function () use ($cliente, $assignment, $actor): void {
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
            $snapshot = ['cliente_id' => $target->cliente_id, 'agente_id' => $target->agente_id, 'es_principal' => $target->es_principal];
            $target->delete();

            $remaining = ClienteAgente::query()
                ->where('cliente_id', $lockedCliente->getKey())
                ->whereHas('agente');
            $remainingCount = (clone $remaining)->count();

            if ($remainingCount === 0) {
                ClienteAgente::query()
                    ->where('cliente_id', $lockedCliente->getKey())
                    ->update(['es_principal' => false]);

                $this->bitacora->record($actor, 'cliente_agente_desasignado', 'cliente_agente', $assignment->getKey(), 'Agente desasignado de Cliente.', $snapshot);

                return;
            }

            $principalCount = (clone $remaining)
                ->where('es_principal', true)
                ->count();

            if (! $wasPrincipal && $principalCount === 1) {
                $this->bitacora->record($actor, 'cliente_agente_desasignado', 'cliente_agente', $assignment->getKey(), 'Agente desasignado de Cliente.', $snapshot);

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

            $this->bitacora->record($actor, 'cliente_agente_desasignado', 'cliente_agente', $assignment->getKey(), 'Agente desasignado; se promovió otro principal.', $snapshot, $next === null ? null : ['cliente_id' => $next->cliente_id, 'agente_id' => $next->agente_id, 'es_principal' => true]);
        });
    }
}
