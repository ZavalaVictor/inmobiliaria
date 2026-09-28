<?php

namespace App\Services\Citas;

use App\Models\Agente;
use App\Models\Cita;
use App\Models\Inmueble;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class CitaAvailabilityService
{
    /**
     * @param  array<int, int>  $agentIds
     * @return array{agentes: array<int, Agente>, inmueble: Inmueble}
     */
    public function lockResources(array $agentIds, int $inmuebleId): array
    {
        $agentes = [];

        foreach (array_unique(array_map('intval', $agentIds)) as $agentId) {
            $agente = Agente::withTrashed()
                ->whereKey($agentId)
                ->lockForUpdate()
                ->first();

            if ($agente === null) {
                throw ValidationException::withMessages([
                    'agente_id' => ['El Agente seleccionado no existe.'],
                ]);
            }

            $agentes[$agentId] = $agente;
        }

        $inmueble = Inmueble::withTrashed()
            ->whereKey($inmuebleId)
            ->lockForUpdate()
            ->first();

        if ($inmueble === null) {
            throw ValidationException::withMessages([
                'inmueble_id' => ['El Inmueble seleccionado no existe.'],
            ]);
        }

        return ['agentes' => $agentes, 'inmueble' => $inmueble];
    }

    public function assertNoOverlap(
        int $agenteId,
        int $inmuebleId,
        Carbon|string $fechaInicio,
        Carbon|string $fechaFin,
        ?int $ignoreCitaId = null,
    ): void {
        $agentConflict = $this->baseConflictQuery($ignoreCitaId)
            ->where('agente_id', $agenteId)
            ->where('fecha_inicio', '<', $fechaFin)
            ->where('fecha_fin', '>', $fechaInicio)
            ->exists();

        $propertyConflict = $this->baseConflictQuery($ignoreCitaId)
            ->where('inmueble_id', $inmuebleId)
            ->where('fecha_inicio', '<', $fechaFin)
            ->where('fecha_fin', '>', $fechaInicio)
            ->exists();

        $errors = [];

        if ($agentConflict) {
            $errors['agente_id'] = ['El Agente ya tiene otra Cita que se traslapa.'];
        }

        if ($propertyConflict) {
            $errors['inmueble_id'] = ['El Inmueble ya tiene otra Cita que se traslapa.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function baseConflictQuery(?int $ignoreCitaId)
    {
        return Cita::query()
            ->whereIn('estado', ['programada', 'confirmada'])
            ->when($ignoreCitaId !== null, fn ($query) => $query->whereKeyNot($ignoreCitaId));
    }
}
