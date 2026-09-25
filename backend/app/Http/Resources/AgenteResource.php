<?php

namespace App\Http\Resources;

use App\Enums\EstadoLaboralAgente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgenteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'numero_empleado' => $this->numero_empleado,
            'telefono_corporativo' => $this->telefono_corporativo,
            'zona_asignacion' => $this->zona_asignacion,
            'horario' => $this->horario,
            'porcentaje_comision' => $this->porcentaje_comision,
            'estado_laboral' => $this->estado_laboral instanceof EstadoLaboralAgente
                ? $this->estado_laboral->value
                : $this->estado_laboral,
            'fecha_contratacion' => $this->fecha_contratacion?->format('Y-m-d'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->whenLoaded('user', function (): array {
                return [
                    'id' => $this->user->id,
                    'nombres' => $this->user->nombres,
                    'apellido_paterno' => $this->user->apellido_paterno,
                    'apellido_materno' => $this->user->apellido_materno,
                    'email' => $this->user->email,
                    'telefono' => $this->user->telefono,
                    'estado' => $this->user->estado?->value ?? $this->user->estado,
                ];
            }),
        ];
    }
}
