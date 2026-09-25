<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteInmuebleInteresResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'inmueble_id' => $this->inmueble_id,
            'nivel_interes' => $this->nivel_interes?->value ?? $this->nivel_interes,
            'estado' => $this->estado?->value ?? $this->estado,
            'notas' => $this->notas,
            'fecha_interes' => $this->fecha_interes?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'inmueble' => $this->whenLoaded('inmueble', function (): array {
                return [
                    'id' => $this->inmueble->id,
                    'codigo' => $this->inmueble->codigo,
                    'titulo' => $this->inmueble->titulo,
                    'slug' => $this->inmueble->slug,
                ];
            }),
        ];
    }
}
