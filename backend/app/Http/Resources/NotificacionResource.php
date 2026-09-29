<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : [];
        $entity = is_array($data['entidad'] ?? null) ? $data['entidad'] : null;

        return [
            'id' => $this->id,
            'tipo' => $this->stringValue($data['tipo'] ?? null),
            'titulo' => $this->stringValue($data['titulo'] ?? null),
            'mensaje' => $this->stringValue($data['mensaje'] ?? null),
            'url' => $this->stringValue($data['url'] ?? null),
            'entidad' => $entity === null ? null : [
                'tipo' => $this->stringValue($entity['tipo'] ?? null),
                'id' => $this->scalarValue($entity['id'] ?? null),
            ],
            'leida' => $this->read_at !== null,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function scalarValue(mixed $value): int|string|null
    {
        return is_int($value) || is_string($value) ? $value : null;
    }
}
