<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgenteInmuebleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'agente_id' => $this->agente_id,
            'inmueble_id' => $this->inmueble_id,
            'es_principal' => $this->es_principal,
            'fecha_asignacion' => $this->fecha_asignacion?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        $data['agente'] = $this->whenLoaded('agente', function (): array {
            $data = [
                'id' => $this->agente->id,
                'numero_empleado' => $this->agente->numero_empleado,
            ];

            if ($this->agente->relationLoaded('user')) {
                $data['user'] = [
                    'id' => $this->agente->user->id,
                    'nombres' => $this->agente->user->nombres,
                    'apellido_paterno' => $this->agente->user->apellido_paterno,
                    'apellido_materno' => $this->agente->user->apellido_materno,
                    'email' => $this->agente->user->email,
                    'telefono' => $this->agente->user->telefono,
                    'estado' => $this->agente->user->estado?->value ?? $this->agente->user->estado,
                ];
            }

            return $data;
        });

        return $data;
    }
}
