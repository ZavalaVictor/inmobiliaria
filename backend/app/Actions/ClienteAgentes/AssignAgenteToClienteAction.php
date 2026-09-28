<?php

namespace App\Actions\ClienteAgentes;

use App\Models\Agente;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssignAgenteToClienteAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Cliente $cliente, array $attributes, ?User $actor = null): ClienteAgente
    {
        return DB::transaction(function () use ($cliente, $attributes, $actor): ClienteAgente {
            $lockedCliente = Cliente::query()
                ->whereKey($cliente->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $agentId = (int) $attributes['agente_id'];
            $agent = Agente::query()
                ->whereKey($agentId)
                ->whereNull('deleted_at')
                ->first();

            if ($agent === null) {
                throw ValidationException::withMessages([
                    'agente_id' => ['El Agente seleccionado no existe o no está disponible.'],
                ]);
            }

            $duplicate = ClienteAgente::query()
                ->where('cliente_id', $lockedCliente->getKey())
                ->where('agente_id', $agent->getKey())
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'agente_id' => ['El Agente ya está asignado a este Cliente.'],
                ]);
            }

            $hasEligibleAssignments = ClienteAgente::query()
                ->where('cliente_id', $lockedCliente->getKey())
                ->whereHas('agente')
                ->exists();
            $isPrincipal = $hasEligibleAssignments
                ? (bool) ($attributes['es_principal'] ?? false)
                : true;

            if ($isPrincipal) {
                ClienteAgente::query()
                    ->where('cliente_id', $lockedCliente->getKey())
                    ->update(['es_principal' => false]);
            }

            $assignment = ClienteAgente::create([
                'agente_id' => $agent->getKey(),
                'cliente_id' => $lockedCliente->getKey(),
                'es_principal' => $isPrincipal,
            ]);

            $this->bitacora->record($actor, 'cliente_agente_asignado', 'cliente_agente', $assignment->getKey(), $isPrincipal ? 'Agente asignado como principal a Cliente.' : 'Agente asignado a Cliente.', null, ['cliente_id' => $assignment->cliente_id, 'agente_id' => $assignment->agente_id, 'es_principal' => $assignment->es_principal]);

            return $assignment->fresh([
                'agente:id,user_id,numero_empleado',
                'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
            ]);
        });
    }
}
