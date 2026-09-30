<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PortalClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => trim(implode(' ', array_filter([
                $this->nombres,
                $this->apellido_paterno,
                $this->apellido_materno,
            ]))),
            'correo' => $this->email,
            'telefono' => $this->telefono,
        ];
    }
}
