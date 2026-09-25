<?php

namespace App\Actions\AgenteInmuebles;

use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Inmueble;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssignAgenteToInmuebleAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Inmueble $inmueble, array $attributes): AgenteInmueble
    {
        return DB::transaction(function () use ($inmueble, $attributes): AgenteInmueble {
            $lockedInmueble = Inmueble::query()
                ->whereKey($inmueble->getKey())
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

            $duplicate = AgenteInmueble::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->where('agente_id', $agent->getKey())
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'agente_id' => ['El Agente ya está asignado a este Inmueble.'],
                ]);
            }

            $hasActiveAssignments = AgenteInmueble::query()
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->whereHas('agente')
                ->exists();
            $isPrincipal = $hasActiveAssignments
                ? (bool) ($attributes['es_principal'] ?? false)
                : true;

            if ($isPrincipal) {
                AgenteInmueble::query()
                    ->where('inmueble_id', $lockedInmueble->getKey())
                    ->update(['es_principal' => false]);
            }

            $assignment = AgenteInmueble::create([
                'agente_id' => $agent->getKey(),
                'inmueble_id' => $lockedInmueble->getKey(),
                'es_principal' => $isPrincipal,
            ]);

            return $assignment->fresh([
                'agente:id,user_id,numero_empleado',
                'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
            ]);
        });
    }
}
