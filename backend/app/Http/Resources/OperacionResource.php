<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'oportunidad_id' => $this->oportunidad_id,
            'cliente_id' => $this->cliente_id,
            'inmueble_id' => $this->inmueble_id,
            'registrado_por_user_id' => $this->registrado_por_user_id,
            'tipo_operacion' => $this->tipo_operacion?->value ?? $this->tipo_operacion,
            'monto' => $this->monto,
            'fecha_operacion' => $this->fecha_operacion?->toISOString(),
            'fecha_inicio_contrato' => $this->fecha_inicio_contrato?->format('Y-m-d'),
            'fecha_fin_contrato' => $this->fecha_fin_contrato?->format('Y-m-d'),
            'estado' => $this->estado?->value ?? $this->estado,
            'observaciones' => $this->observaciones,
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
            'registrado_por' => $this->whenLoaded('registradoPor', function (): ?array {
                return $this->registradoPor === null ? null : [
                    'id' => $this->registradoPor->id,
                    'nombres' => $this->registradoPor->nombres,
                    'apellido_paterno' => $this->registradoPor->apellido_paterno,
                    'apellido_materno' => $this->registradoPor->apellido_materno,
                ];
            }),
            'agentes' => OperacionAgenteResource::collection($this->whenLoaded('asignacionesAgentes')),
        ];
    }
}
