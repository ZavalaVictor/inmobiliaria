<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PortalCitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_inicio' => $this->fecha_inicio?->toISOString(),
            'fecha_fin' => $this->fecha_fin?->toISOString(),
            'estado' => $this->estado?->value ?? $this->estado,
            'inmueble' => new PortalInmuebleResource($this->whenLoaded('inmueble')),
            'agente' => $this->whenLoaded('agente', function (): ?array {
                if ($this->agente === null || $this->agente->user === null) {
                    return null;
                }

                return [
                    'id' => $this->agente->id,
                    'nombre' => trim(implode(' ', array_filter([
                        $this->agente->user->nombres,
                        $this->agente->user->apellido_paterno,
                        $this->agente->user->apellido_materno,
                    ]))),
                ];
            }),
            'reprogramaciones' => $this->whenLoaded('historial', fn (): array => $this->historial->map(static fn ($history): array => [
                'fecha_inicio_anterior' => $history->fecha_inicio_anterior?->toISOString(),
                'fecha_fin_anterior' => $history->fecha_fin_anterior?->toISOString(),
                'fecha_inicio_nueva' => $history->fecha_inicio_nueva?->toISOString(),
                'fecha_fin_nueva' => $history->fecha_fin_nueva?->toISOString(),
                'tipo_cambio' => $history->tipo_cambio?->value ?? $history->tipo_cambio,
                'fecha_modificacion' => $history->fecha_modificacion?->toISOString(),
            ])->values()->all()),
        ];
    }
}
