<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PortalInmuebleInteresResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nivel_interes' => $this->nivel_interes?->value ?? $this->nivel_interes,
            'estado' => $this->estado?->value ?? $this->estado,
            'fecha_interes' => $this->fecha_interes?->toISOString(),
            'inmueble' => new PortalInmuebleResource($this->whenLoaded('inmueble')),
        ];
    }
}
