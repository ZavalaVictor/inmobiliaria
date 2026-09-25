<?php

namespace App\Http\Resources;

use App\Enums\EstadoCliente;
use App\Enums\TipoInteresCliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombres' => $this->nombres,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'tipo_interes' => $this->tipo_interes instanceof TipoInteresCliente
                ? $this->tipo_interes->value
                : $this->tipo_interes,
            'presupuesto_min' => $this->presupuesto_min,
            'presupuesto_max' => $this->presupuesto_max,
            'preferencias' => $this->preferencias,
            'estado_cliente' => $this->estado_cliente instanceof EstadoCliente
                ? $this->estado_cliente->value
                : $this->estado_cliente,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->whenLoaded('user', function (): array {
                return [
                    'id' => $this->user->id,
                    'nombres' => $this->user->nombres,
                    'apellido_paterno' => $this->user->apellido_paterno,
                    'apellido_materno' => $this->user->apellido_materno,
                    'email' => $this->user->email,
                ];
            }),
            'agentes' => $this->whenLoaded('agentes', function (): array {
                return $this->agentes->map(static fn ($agente): array => [
                    'id' => $agente->id,
                    'numero_empleado' => $agente->numero_empleado,
                ])->values()->all();
            }),
            'intereses' => $this->whenLoaded('interesesInmuebles', function (): array {
                return $this->interesesInmuebles->map(static fn ($interes): array => [
                    'id' => $interes->id,
                    'inmueble_id' => $interes->inmueble_id,
                    'nivel_interes' => $interes->nivel_interes?->value ?? $interes->nivel_interes,
                    'estado' => $interes->estado?->value ?? $interes->estado,
                    'notas' => $interes->notas,
                    'fecha_interes' => $interes->fecha_interes?->toISOString(),
                ])->values()->all();
            }),
        ];
    }
}
