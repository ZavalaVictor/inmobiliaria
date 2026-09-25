<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteraccionClienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'registrado_por_user_id' => $this->registrado_por_user_id,
            'tipo' => $this->tipo?->value ?? $this->tipo,
            'descripcion' => $this->descripcion,
            'resultado' => $this->resultado,
            'fecha_interaccion' => $this->fecha_interaccion?->toISOString(),
            'proxima_accion' => $this->proxima_accion,
            'fecha_proxima_accion' => $this->fecha_proxima_accion?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'cliente' => $this->whenLoaded('cliente', function (): array {
                return [
                    'id' => $this->cliente->id,
                    'nombres' => $this->cliente->nombres,
                    'apellido_paterno' => $this->cliente->apellido_paterno,
                    'apellido_materno' => $this->cliente->apellido_materno,
                ];
            }),
            'registrado_por' => $this->whenLoaded('registradoPor', function (): array {
                return [
                    'id' => $this->registradoPor->id,
                    'nombres' => $this->registradoPor->nombres,
                    'apellido_paterno' => $this->registradoPor->apellido_paterno,
                    'apellido_materno' => $this->registradoPor->apellido_materno,
                ];
            }),
        ];
    }
}
