<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OportunidadResource extends JsonResource
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
            'agente_principal_id' => $this->agente_principal_id,
            'solicitud_informacion_id' => $this->solicitud_informacion_id,
            'titulo' => $this->titulo,
            'etapa' => $this->etapa?->value ?? $this->etapa,
            'estado' => $this->estado?->value ?? $this->estado,
            'notas' => $this->notas,
            'fecha_apertura' => $this->fecha_apertura?->toISOString(),
            'fecha_cierre' => $this->fecha_cierre?->toISOString(),
            'motivo_perdida' => $this->motivo_perdida,
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
            'agente_principal' => $this->whenLoaded('agentePrincipal', function (): ?array {
                if ($this->agentePrincipal === null) {
                    return null;
                }

                return [
                    'id' => $this->agentePrincipal->id,
                    'numero_empleado' => $this->agentePrincipal->numero_empleado,
                    'user' => $this->when($this->agentePrincipal->relationLoaded('user'), function (): ?array {
                        if ($this->agentePrincipal->user === null) {
                            return null;
                        }

                        return [
                            'id' => $this->agentePrincipal->user->id,
                            'nombres' => $this->agentePrincipal->user->nombres,
                            'apellido_paterno' => $this->agentePrincipal->user->apellido_paterno,
                            'apellido_materno' => $this->agentePrincipal->user->apellido_materno,
                        ];
                    }),
                ];
            }),
            'solicitud_informacion' => $this->whenLoaded('solicitudInformacion', function (): ?array {
                if ($this->solicitudInformacion === null) {
                    return null;
                }

                return [
                    'id' => $this->solicitudInformacion->id,
                    'nombre' => $this->solicitudInformacion->nombre,
                    'estado' => $this->solicitudInformacion->estado?->value ?? $this->solicitudInformacion->estado,
                    'medio_preferido' => $this->solicitudInformacion->medio_preferido?->value ?? $this->solicitudInformacion->medio_preferido,
                ];
            }),
            'historial' => OportunidadHistorialResource::collection($this->whenLoaded('historial')),
        ];
    }
}
