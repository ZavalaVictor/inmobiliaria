<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InmuebleImagenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inmueble_id' => $this->inmueble_id,
            'url_publica' => $this->url_publica,
            'nombre_original' => $this->nombre_original,
            'mime_type' => $this->mime_type,
            'tamano_bytes' => $this->tamano_bytes,
            'es_principal' => $this->es_principal,
            'orden' => $this->orden,
            'texto_alternativo' => $this->texto_alternativo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
