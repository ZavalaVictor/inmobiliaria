<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BitacoraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'accion' => $this->accion,
            'entidad' => $this->entidad,
            'entidad_id' => $this->entidad_id,
            'descripcion' => $this->descripcion,
            'fecha_evento' => $this->fecha_evento?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'actor' => $this->whenLoaded('user', function (): ?array {
                return $this->user === null ? null : [
                    'id' => $this->user->id,
                    'nombres' => $this->user->nombres,
                    'apellido_paterno' => $this->user->apellido_paterno,
                    'apellido_materno' => $this->user->apellido_materno,
                ];
            }),
        ];
    }
}
