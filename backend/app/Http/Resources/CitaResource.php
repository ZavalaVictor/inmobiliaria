<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'agente_id' => $this->agente_id,
            'inmueble_id' => $this->inmueble_id,
            'oportunidad_id' => $this->oportunidad_id,
            'creado_por_user_id' => $this->creado_por_user_id,
            'fecha_inicio' => $this->fecha_inicio?->toISOString(),
            'fecha_fin' => $this->fecha_fin?->toISOString(),
            'estado' => $this->estado?->value ?? $this->estado,
            'motivo' => $this->motivo,
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'cliente' => $this->whenLoaded('cliente', function (): ?array {
                return $this->cliente === null ? null : [
                    'id' => $this->cliente->id,
                    'nombres' => $this->cliente->nombres,
                    'apellido_paterno' => $this->cliente->apellido_paterno,
                    'apellido_materno' => $this->cliente->apellido_materno,
                ];
            }),
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
            'inmueble' => $this->whenLoaded('inmueble', function (): ?array {
                return $this->inmueble === null ? null : [
                    'id' => $this->inmueble->id,
                    'codigo' => $this->inmueble->codigo,
                    'titulo' => $this->inmueble->titulo,
                    'slug' => $this->inmueble->slug,
                    'publicado' => $this->inmueble->publicado,
                ];
            }),
            'oportunidad' => $this->whenLoaded('oportunidad', function (): ?array {
                return $this->oportunidad === null ? null : [
                    'id' => $this->oportunidad->id,
                    'titulo' => $this->oportunidad->titulo,
                    'etapa' => $this->oportunidad->etapa?->value ?? $this->oportunidad->etapa,
                    'estado' => $this->oportunidad->estado?->value ?? $this->oportunidad->estado,
                ];
            }),
            'creado_por' => $this->whenLoaded('creadoPor', function (): ?array {
                return $this->creadoPor === null ? null : [
                    'id' => $this->creadoPor->id,
                    'nombres' => $this->creadoPor->nombres,
                    'apellido_paterno' => $this->creadoPor->apellido_paterno,
                    'apellido_materno' => $this->creadoPor->apellido_materno,
                ];
            }),
        ];
    }
}
