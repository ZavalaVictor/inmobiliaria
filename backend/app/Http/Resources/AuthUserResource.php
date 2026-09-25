<?php

namespace App\Http\Resources;

use App\Enums\EstadoUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->resource->getRoleNames()->values();
        $permissions = $this->resource->getAllPermissions()->pluck('name')->values();

        return [
            'id' => $this->id,
            'nombres' => $this->nombres,
            'apellidos' => trim(implode(' ', array_filter([
                $this->apellido_paterno,
                $this->apellido_materno,
            ]))),
            'email' => $this->email,
            'telefono' => $this->telefono,
            'estado' => $this->estado instanceof EstadoUsuario
                ? $this->estado->value
                : $this->estado,
            'roles' => $roles,
            'permisos' => $permissions,
        ];
    }
}
