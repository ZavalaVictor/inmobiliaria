<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisualizacionInmuebleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inmueble_id' => $this->inmueble_id,
            'origen' => $this->origen?->value ?? $this->origen,
            'fecha_visualizacion' => $this->fecha_visualizacion?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'inmueble' => $this->whenLoaded('inmueble', function (): ?array {
                return $this->inmueble === null ? null : [
                    'id' => $this->inmueble->id,
                    'codigo' => $this->inmueble->codigo,
                    'titulo' => $this->inmueble->titulo,
                    'slug' => $this->inmueble->slug,
                    'publicado' => $this->inmueble->publicado,
                ];
            }),
        ];
    }
}
