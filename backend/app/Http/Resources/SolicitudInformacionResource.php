<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudInformacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inmueble_id' => $this->inmueble_id,
            'cliente_id' => $this->cliente_id,
            'atendida_por_user_id' => $this->atendida_por_user_id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'mensaje' => $this->mensaje,
            'medio_preferido' => $this->medio_preferido?->value ?? $this->medio_preferido,
            'estado' => $this->estado?->value ?? $this->estado,
            'origen' => $this->origen,
            'fecha_solicitud' => $this->fecha_solicitud?->toISOString(),
            'fecha_atencion' => $this->fecha_atencion?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'cliente' => $this->whenLoaded('cliente', function (): ?array {
                if ($this->cliente === null) {
                    return null;
                }

                return [
                    'id' => $this->cliente->id,
                    'nombres' => $this->cliente->nombres,
                    'apellido_paterno' => $this->cliente->apellido_paterno,
                    'apellido_materno' => $this->cliente->apellido_materno,
                ];
            }),
            'inmueble' => $this->whenLoaded('inmueble', function (): ?array {
                if ($this->inmueble === null) {
                    return null;
                }

                return [
                    'id' => $this->inmueble->id,
                    'codigo' => $this->inmueble->codigo,
                    'titulo' => $this->inmueble->titulo,
                    'slug' => $this->inmueble->slug,
                    'publicado' => $this->inmueble->publicado,
                ];
            }),
            'atendida_por' => $this->whenLoaded('atendidaPor', function (): ?array {
                if ($this->atendidaPor === null) {
                    return null;
                }

                return [
                    'id' => $this->atendidaPor->id,
                    'nombres' => $this->atendidaPor->nombres,
                    'apellido_paterno' => $this->atendidaPor->apellido_paterno,
                    'apellido_materno' => $this->atendidaPor->apellido_materno,
                ];
            }),
        ];
    }
}
