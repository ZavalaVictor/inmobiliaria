<?php

namespace App\Actions\ClienteAgentes;

use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class SetPrincipalClienteAgenteAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(Cliente $cliente, ClienteAgente $assignment, ?User $actor = null): ClienteAgente
    {
        return DB::transaction(function () use ($cliente, $assignment, $actor): ClienteAgente {
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

            if ((bool) $target->es_principal) {
                return $target->fresh([
                    'agente:id,user_id,numero_empleado',
                    'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
                ]);
            }

            ClienteAgente::query()
                ->where('cliente_id', $lockedCliente->getKey())
                ->update(['es_principal' => false]);

            ClienteAgente::query()
                ->whereKey($target->getKey())
                ->update(['es_principal' => true]);

            $this->bitacora->record($actor, 'cliente_agente_principal_cambiado', 'cliente_agente', $target->getKey(), 'Principal de Cliente cambiado.', null, ['cliente_id' => $target->cliente_id, 'agente_id' => $target->agente_id, 'es_principal' => true]);

            return $target->fresh([
                'agente:id,user_id,numero_empleado',
                'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
            ]);
        });
    }
}
