<?php

namespace App\Actions\Citas;

use App\Models\Agente;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Oportunidad;
use App\Models\User;
use App\Services\Citas\CitaAvailabilityService;
use App\Services\Citas\CitaNotificationDispatcher;
use App\Support\Authorization\ActorScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateCitaAction
{
    public function __construct(
        private readonly CitaAvailabilityService $availability,
        private readonly CitaNotificationDispatcher $notifications,
    ) {}

    public function execute(User $user, array $attributes): Cita
    {
        $values = array_intersect_key($attributes, array_flip([
            'cliente_id', 'agente_id', 'inmueble_id', 'oportunidad_id',
            'fecha_inicio', 'fecha_fin', 'motivo', 'notas',
        ]));

        $cita = DB::transaction(function () use ($user, $values): Cita {
            $cliente = Cliente::query()->whereKey($values['cliente_id'])->first();
            $inmueble = Inmueble::query()->whereKey($values['inmueble_id'])->first();

            if ($cliente === null) {
                throw ValidationException::withMessages(['cliente_id' => ['El Cliente no existe o no está disponible.']]);
            }

            if ($inmueble === null) {
                throw ValidationException::withMessages(['inmueble_id' => ['El Inmueble no existe o no está disponible.']]);
            }

            $agenteId = $this->resolveAgent($user, $values);
            $agente = Agente::query()->whereKey($agenteId)->first();

            if ($agente === null) {
                throw ValidationException::withMessages(['agente_id' => ['El Agente no existe o no está disponible.']]);
            }

            $this->validateOpportunity($user, $values['oportunidad_id'] ?? null, $cliente, $inmueble);

            $this->availability->lockResources([$agenteId], $inmueble->getKey());
            $this->assertAssignments($cliente, $inmueble, $agenteId);
            $this->availability->assertNoOverlap(
                $agenteId,
                $inmueble->getKey(),
                $values['fecha_inicio'],
                $values['fecha_fin'],
            );

            return Cita::create([
                'cliente_id' => $cliente->getKey(),
                'agente_id' => $agenteId,
                'inmueble_id' => $inmueble->getKey(),
                'oportunidad_id' => $values['oportunidad_id'] ?? null,
                'creado_por_user_id' => $user->getKey(),
                'fecha_inicio' => $values['fecha_inicio'],
                'fecha_fin' => $values['fecha_fin'],
                'estado' => 'programada',
                'motivo' => $values['motivo'] ?? null,
                'notas' => $values['notas'] ?? null,
            ]);
        });

        $cita = $cita->fresh($this->relations());
        $this->notifications->created($cita, $user);

        return $cita;
    }

    private function resolveAgent(User $user, array $values): int
    {
        if (ActorScope::isAgent($user)) {
            $agentId = ActorScope::agentId($user);

            if ($agentId === null) {
                throw ValidationException::withMessages(['agente_id' => ['El perfil Agente no está disponible.']]);
            }

            return $agentId;
        }

        return (int) $values['agente_id'];
    }

    private function assertAssignments(Cliente $cliente, Inmueble $inmueble, int $agenteId): void
    {
        $clientAssigned = $cliente->asignacionesAgentes()
            ->where('agente_id', $agenteId)
            ->whereHas('agente')
            ->exists();
        $propertyAssigned = $inmueble->asignacionesAgentes()
            ->where('agente_id', $agenteId)
            ->whereHas('agente')
            ->exists();

        if (! $clientAssigned || ! $propertyAssigned) {
            throw ValidationException::withMessages([
                'agente_id' => ['El Agente debe estar asignado al Cliente y al Inmueble.'],
            ]);
        }
    }

    private function validateOpportunity(User $user, mixed $opportunityId, Cliente $cliente, Inmueble $inmueble): void
    {
        if ($opportunityId === null) {
            return;
        }

        $opportunity = Oportunidad::query()->whereKey($opportunityId)->first();

        if ($opportunity === null) {
            throw ValidationException::withMessages(['oportunidad_id' => ['La Oportunidad no existe o no está disponible.']]);
        }

        if ($opportunity->cliente_id !== $cliente->getKey()) {
            throw ValidationException::withMessages(['oportunidad_id' => ['La Oportunidad no corresponde al Cliente.']]);
        }

        if ($opportunity->inmueble_id !== null && $opportunity->inmueble_id !== $inmueble->getKey()) {
            throw ValidationException::withMessages(['oportunidad_id' => ['La Oportunidad no corresponde al Inmueble.']]);
        }

        if (ActorScope::isAgent($user) && Gate::forUser($user)->denies('view', $opportunity)) {
            throw ValidationException::withMessages(['oportunidad_id' => ['El Agente no tiene alcance sobre la Oportunidad.']]);
        }
    }

    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'agente:id,numero_empleado,user_id',
            'agente.user:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'oportunidad:id,titulo,etapa,estado',
            'creadoPor:id,nombres,apellido_paterno,apellido_materno',
        ];
    }
}
