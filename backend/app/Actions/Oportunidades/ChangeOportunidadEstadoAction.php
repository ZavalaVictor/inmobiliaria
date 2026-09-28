<?php

namespace App\Actions\Oportunidades;

use App\Models\Oportunidad;
use App\Models\OportunidadHistorial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ChangeOportunidadEstadoAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, Oportunidad $oportunidad, array $attributes): Oportunidad
    {
        $updated = DB::transaction(function () use ($user, $oportunidad, $attributes): Oportunidad {
            $locked = Oportunidad::query()
                ->whereKey($oportunidad->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $current = $locked->estado?->value ?? $locked->estado;
            $next = $attributes['estado'];

            if ($current === $next) {
                return $locked;
            }

            $locked->update(['estado' => $next]);

            OportunidadHistorial::create([
                'oportunidad_id' => $locked->getKey(),
                'cambiado_por_user_id' => $user->getKey(),
                'tipo_evento' => 'cambio_estado',
                'etapa_anterior' => null,
                'etapa_nueva' => null,
                'estado_anterior' => $current,
                'estado_nuevo' => $next,
                'comentario' => $attributes['comentario'] ?? null,
            ]);

            return $locked;
        });

        return $updated->fresh($this->relations());
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'agentePrincipal:id,numero_empleado,user_id',
            'agentePrincipal.user:id,nombres,apellido_paterno,apellido_materno',
            'solicitudInformacion:id,nombre,estado,medio_preferido',
        ];
    }
}
