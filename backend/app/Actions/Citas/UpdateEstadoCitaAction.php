<?php

namespace App\Actions\Citas;

use App\Models\Cita;
use App\Models\User;
use App\Services\Citas\CitaAvailabilityService;
use App\Services\Citas\CitaNotificationDispatcher;
use Illuminate\Support\Facades\DB;

final class UpdateEstadoCitaAction
{
    public function __construct(
        private readonly CitaAvailabilityService $availability,
        private readonly CitaNotificationDispatcher $notifications,
    ) {}

    public function execute(User $user, Cita $cita, array $attributes): Cita
    {
        [$updated, $cancelled] = DB::transaction(function () use ($cita, $attributes): array {
            $locked = Cita::query()->whereKey($cita->getKey())->lockForUpdate()->firstOrFail();
            $next = $attributes['estado'];
            $current = $locked->estado?->value ?? $locked->estado;

            $this->availability->lockResources([$locked->agente_id], $locked->inmueble_id);

            if ($current !== $next
                && in_array($next, ['programada', 'confirmada'], true)) {
                $this->availability->assertNoOverlap(
                    $locked->agente_id,
                    $locked->inmueble_id,
                    $locked->fecha_inicio,
                    $locked->fecha_fin,
                    $locked->getKey(),
                );
            }

            if ($current !== $next) {
                $locked->update(['estado' => $next]);
            }

            return [$locked, $current !== 'cancelada' && $next === 'cancelada'];
        });

        $updated = $updated->fresh($this->relations());

        if ($cancelled) {
            $this->notifications->cancelled($updated, $user);
        }

        return $updated;
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
