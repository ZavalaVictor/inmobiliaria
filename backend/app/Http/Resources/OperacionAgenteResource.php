<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperacionAgenteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'operacion_id' => $this->operacion_id,
            'agente_id' => $this->agente_id,
            'es_principal' => $this->es_principal,
            'porcentaje_comision' => $this->porcentaje_comision,
            'monto_comision' => $this->monto_comision,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'agente' => $this->whenLoaded('agente', function (): ?array {
                if ($this->agente === null) {
                    return null;
                }

                return [
                    'id' => $this->agente->id,
                    'numero_empleado' => $this->agente->numero_empleado,
                    'user' => $this->when($this->agente->relationLoaded('user'), function (): ?array {
                        return $this->agente->user === null ? null : [
                            'id' => $this->agente->user->id,
                            'nombres' => $this->agente->user->nombres,
                            'apellido_paterno' => $this->agente->user->apellido_paterno,
                            'apellido_materno' => $this->agente->user->apellido_materno,
                        ];
                    }),
                ];
            }),
        ];
    }
}
