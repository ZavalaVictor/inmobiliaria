<?php

namespace App\Actions\Citas;

use App\Models\Agente;
use App\Models\Cita;
use App\Models\CitaHistorial;
use App\Models\User;
use App\Services\Citas\CitaAvailabilityService;
use App\Services\Citas\CitaNotificationDispatcher;
use App\Support\Authorization\ActorScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReprogramarCitaAction
{
    public function __construct(
        private readonly CitaAvailabilityService $availability,
        private readonly CitaNotificationDispatcher $notifications,
    ) {}

    public function execute(User $user, Cita $cita, array $attributes): Cita
    {
        [$updated, $history] = DB::transaction(function () use ($user, $cita, $attributes): array {
            $locked = Cita::query()->whereKey($cita->getKey())->lockForUpdate()->firstOrFail();
            $newAgentId = $this->resolveAgent($user, $locked, $attributes);
            $agentIds = [$locked->agente_id, $newAgentId];
            sort($agentIds, SORT_NUMERIC);
            $this->availability->lockResources($agentIds, $locked->inmueble_id);

            $newStart = Carbon::parse($attributes['fecha_inicio']);
            $newEnd = Carbon::parse($attributes['fecha_fin']);
            $datesChanged = ! $locked->fecha_inicio->equalTo($newStart) || ! $locked->fecha_fin->equalTo($newEnd);
            $agentChanged = (int) $locked->agente_id !== $newAgentId;

            if (! $datesChanged && ! $agentChanged) {
                return [$locked, null];
            }

            $this->assertAgentIsAssigned($locked, $newAgentId);
            $this->availability->assertNoOverlap($newAgentId, $locked->inmueble_id, $newStart, $newEnd, $locked->getKey());

            $oldAgentId = $locked->agente_id;
            $oldStart = $locked->fecha_inicio->copy();
            $oldEnd = $locked->fecha_fin->copy();
            $locked->update([
                'agente_id' => $newAgentId,
                'fecha_inicio' => $newStart,
                'fecha_fin' => $newEnd,
            ]);

            $history = CitaHistorial::create([
                'cita_id' => $locked->getKey(),
                'modificado_por_user_id' => $user->getKey(),
                'agente_anterior_id' => $oldAgentId,
                'agente_nuevo_id' => $newAgentId,
                'fecha_inicio_anterior' => $oldStart,
                'fecha_fin_anterior' => $oldEnd,
                'fecha_inicio_nueva' => $newStart,
                'fecha_fin_nueva' => $newEnd,
                'motivo' => $attributes['motivo'],
                'tipo_cambio' => match (true) {
                    $datesChanged && $agentChanged => 'reprogramacion_y_agente',
                    $agentChanged => 'cambio_agente',
                    default => 'reprogramacion',
                },
            ]);

            return [$locked, $history];
        });

        $updated = $updated->fresh($this->relations());

        if ($history instanceof CitaHistorial) {
            $this->notifications->rescheduled($updated, $history, $user);
        }

        return $updated;
    }

    private function resolveAgent(User $user, Cita $cita, array $attributes): int
    {
        if (ActorScope::isAgent($user)) {
            $agentId = ActorScope::agentId($user);

            if ($agentId === null || $agentId !== (int) $cita->agente_id) {
                throw ValidationException::withMessages(['agente_id' => ['El Agente no puede reasignar esta Cita.']]);
            }

            return $agentId;
        }

        return (int) ($attributes['agente_id'] ?? $cita->agente_id);
    }

    private function assertAgentIsAssigned(Cita $cita, int $agentId): void
    {
        if (! Agente::query()->whereKey($agentId)->exists()) {
            throw ValidationException::withMessages(['agente_id' => ['El Agente no existe o no está disponible.']]);
        }

        $clientAssigned = $cita->cliente->asignacionesAgentes()->where('agente_id', $agentId)->whereHas('agente')->exists();
        $propertyAssigned = $cita->inmueble->asignacionesAgentes()->where('agente_id', $agentId)->whereHas('agente')->exists();

        if (! $clientAssigned || ! $propertyAssigned) {
            throw ValidationException::withMessages(['agente_id' => ['El Agente debe estar asignado al Cliente y al Inmueble.']]);
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
