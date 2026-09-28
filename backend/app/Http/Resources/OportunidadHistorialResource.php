<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OportunidadHistorialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'oportunidad_id' => $this->oportunidad_id,
            'cambiado_por_user_id' => $this->cambiado_por_user_id,
            'tipo_evento' => $this->tipo_evento?->value ?? $this->tipo_evento,
            'etapa_anterior' => $this->etapa_anterior?->value ?? $this->etapa_anterior,
            'etapa_nueva' => $this->etapa_nueva?->value ?? $this->etapa_nueva,
            'estado_anterior' => $this->estado_anterior?->value ?? $this->estado_anterior,
            'estado_nuevo' => $this->estado_nuevo?->value ?? $this->estado_nuevo,
            'comentario' => $this->comentario,
            'fecha_cambio' => $this->fecha_cambio?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'cambiado_por' => $this->whenLoaded('cambiadoPor', function (): ?array {
                if ($this->cambiadoPor === null) {
                    return null;
                }

                return [
                    'id' => $this->cambiadoPor->id,
                    'nombres' => $this->cambiadoPor->nombres,
                    'apellido_paterno' => $this->cambiadoPor->apellido_paterno,
                    'apellido_materno' => $this->cambiadoPor->apellido_materno,
                ];
            }),
        ];
    }
}
