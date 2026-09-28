<?php

namespace App\Actions\Citas;

use App\Models\Cita;
use App\Models\User;
use App\Services\Citas\CitaAvailabilityService;
use Illuminate\Support\Facades\DB;

final class UpdateEstadoCitaAction
{
    public function __construct(private readonly CitaAvailabilityService $availability) {}

    public function execute(User $user, Cita $cita, array $attributes): Cita
    {
        $updated = DB::transaction(function () use ($cita, $attributes): Cita {
            $locked = Cita::query()->whereKey($cita->getKey())->lockForUpdate()->firstOrFail();
            $next = $attributes['estado'];

            $this->availability->lockResources([$locked->agente_id], $locked->inmueble_id);

            if (($locked->estado?->value ?? $locked->estado) !== $next
                && in_array($next, ['programada', 'confirmada'], true)) {
                $this->availability->assertNoOverlap(
                    $locked->agente_id,
                    $locked->inmueble_id,
                    $locked->fecha_inicio,
                    $locked->fecha_fin,
                    $locked->getKey(),
                );
            }

            if (($locked->estado?->value ?? $locked->estado) !== $next) {
                $locked->update(['estado' => $next]);
            }

            return $locked;
        });

        return $updated->fresh($this->relations());
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
