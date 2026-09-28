<?php

namespace App\Actions\Agentes;

use App\Models\Agente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;

final class DeleteAgenteAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(Agente $agente, ?User $actor = null): void
    {
        DB::transaction(function () use ($agente, $actor): void {
            $before = [
                'numero_empleado' => $agente->numero_empleado,
                'telefono_corporativo' => $agente->telefono_corporativo,
                'zona_asignacion' => $agente->zona_asignacion,
                'horario' => $agente->horario,
                'porcentaje_comision' => $agente->porcentaje_comision,
                'estado_laboral' => $agente->estado_laboral,
                'fecha_contratacion' => $agente->fecha_contratacion,
            ];
            $agente->delete();
            $this->bitacora->record($actor, 'agente_eliminado', 'agente', $agente->getKey(), 'Agente eliminado.', $before);
        });
    }
}
