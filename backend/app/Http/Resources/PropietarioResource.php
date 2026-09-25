<?php

namespace App\Http\Resources;

use App\Enums\EstadoRegistroPropietario;
use App\Enums\TipoPersonaPropietario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropietarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_persona' => $this->tipo_persona instanceof TipoPersonaPropietario
                ? $this->tipo_persona->value
                : $this->tipo_persona,
            'nombre_razon_social' => $this->nombre_razon_social,
            'rfc' => $this->rfc,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'estado_registro' => $this->estado_registro instanceof EstadoRegistroPropietario
                ? $this->estado_registro->value
                : $this->estado_registro,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
