<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BitacoraDetailResource extends BitacoraResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'datos_anteriores' => $this->datos_anteriores,
            'datos_nuevos' => $this->datos_nuevos,
        ];
    }
}
