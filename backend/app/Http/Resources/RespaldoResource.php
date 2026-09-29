<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RespaldoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo?->value ?? $this->tipo,
            'estado' => $this->estado?->value ?? $this->estado,
            'nombre_archivo' => $this->nombre_archivo,
            'tamano_bytes' => $this->tamano_bytes,
            'fecha_inicio' => $this->fecha_inicio?->toISOString(),
            'fecha_finalizacion' => $this->fecha_finalizacion?->toISOString(),
            'estado_restauracion' => $this->estado_restauracion?->value ?? $this->estado_restauracion,
            'restaurado_at' => $this->restaurado_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'generado_por' => $this->whenLoaded('generadoPor', fn (): ?array => $this->userResource($this->generadoPor)),
            'restaurado_por' => $this->whenLoaded('restauradoPor', fn (): ?array => $this->userResource($this->restauradoPor)),
            'download_endpoint' => '/api/v1/respaldos/'.$this->id.'/descargar',
        ];
    }

    /** @return array<string, mixed>|null */
    private function userResource(?object $user): ?array
    {
        return $user === null ? null : [
            'id' => $user->id,
            'nombres' => $user->nombres,
            'apellido_paterno' => $user->apellido_paterno,
            'apellido_materno' => $user->apellido_materno,
        ];
    }
}
