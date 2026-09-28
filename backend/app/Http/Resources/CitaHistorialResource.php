<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaHistorialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cita_id' => $this->cita_id,
            'modificado_por_user_id' => $this->modificado_por_user_id,
            'agente_anterior_id' => $this->agente_anterior_id,
            'agente_nuevo_id' => $this->agente_nuevo_id,
            'fecha_inicio_anterior' => $this->fecha_inicio_anterior?->toISOString(),
            'fecha_fin_anterior' => $this->fecha_fin_anterior?->toISOString(),
            'fecha_inicio_nueva' => $this->fecha_inicio_nueva?->toISOString(),
            'fecha_fin_nueva' => $this->fecha_fin_nueva?->toISOString(),
            'motivo' => $this->motivo,
            'tipo_cambio' => $this->tipo_cambio?->value ?? $this->tipo_cambio,
            'fecha_modificacion' => $this->fecha_modificacion?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'modificado_por' => $this->whenLoaded('modificadoPor', function (): ?array {
                return $this->modificadoPor === null ? null : [
                    'id' => $this->modificadoPor->id,
                    'nombres' => $this->modificadoPor->nombres,
                    'apellido_paterno' => $this->modificadoPor->apellido_paterno,
                    'apellido_materno' => $this->modificadoPor->apellido_materno,
                ];
            }),
            'agente_anterior' => $this->whenLoaded('agenteAnterior', fn (): ?array => $this->limitedAgent($this->agenteAnterior)),
            'agente_nuevo' => $this->whenLoaded('agenteNuevo', fn (): ?array => $this->limitedAgent($this->agenteNuevo)),
        ];
    }

    private function limitedAgent($agent): ?array
    {
        return $agent === null ? null : [
            'id' => $agent->id,
            'numero_empleado' => $agent->numero_empleado,
            'user' => $agent->relationLoaded('user') && $agent->user !== null ? [
                'id' => $agent->user->id,
                'nombres' => $agent->user->nombres,
                'apellido_paterno' => $agent->user->apellido_paterno,
                'apellido_materno' => $agent->user->apellido_materno,
            ] : null,
        ];
    }
}
