<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categoria_documento_id' => $this->categoria_documento_id,
            'subido_por_user_id' => $this->subido_por_user_id,
            'propietario_id' => $this->propietario_id,
            'cliente_id' => $this->cliente_id,
            'inmueble_id' => $this->inmueble_id,
            'operacion_id' => $this->operacion_id,
            'nombre_original' => $this->nombre_original,
            'mime_type' => $this->mime_type?->value ?? $this->mime_type,
            'tamano_bytes' => $this->tamano_bytes,
            'fecha_documento' => $this->fecha_documento?->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
            'observaciones' => $this->observaciones,
            'download_endpoint' => route('documentos.download', ['documento' => $this->getKey()]),
            'categoria' => $this->whenLoaded('categoriaDocumento', function (): ?array {
                return $this->categoriaDocumento === null ? null : [
                    'id' => $this->categoriaDocumento->id,
                    'nombre' => $this->categoriaDocumento->nombre,
                    'activo' => $this->categoriaDocumento->activo,
                ];
            }),
            'subido_por' => $this->whenLoaded('subidoPor', function (): ?array {
                return $this->subidoPor === null ? null : [
                    'id' => $this->subidoPor->id,
                    'nombres' => $this->subidoPor->nombres,
                    'apellido_paterno' => $this->subidoPor->apellido_paterno,
                    'apellido_materno' => $this->subidoPor->apellido_materno,
                ];
            }),
            'propietario' => $this->whenLoaded('propietario', function (): ?array {
                return $this->propietario === null ? null : [
                    'id' => $this->propietario->id,
                    'nombre_razon_social' => $this->propietario->nombre_razon_social,
                ];
            }),
            'cliente' => $this->whenLoaded('cliente', function (): ?array {
                return $this->cliente === null ? null : [
                    'id' => $this->cliente->id,
                    'nombres' => $this->cliente->nombres,
                    'apellido_paterno' => $this->cliente->apellido_paterno,
                    'apellido_materno' => $this->cliente->apellido_materno,
                ];
            }),
            'inmueble' => $this->whenLoaded('inmueble', function (): ?array {
                return $this->inmueble === null ? null : [
                    'id' => $this->inmueble->id,
                    'codigo' => $this->inmueble->codigo,
                    'titulo' => $this->inmueble->titulo,
                    'slug' => $this->inmueble->slug,
                    'publicado' => $this->inmueble->publicado,
                ];
            }),
            'operacion' => $this->whenLoaded('operacion', function (): ?array {
                return $this->operacion === null ? null : [
                    'id' => $this->operacion->id,
                    'tipo_operacion' => $this->operacion->tipo_operacion?->value ?? $this->operacion->tipo_operacion,
                    'monto' => $this->operacion->monto,
                    'estado' => $this->operacion->estado?->value ?? $this->operacion->estado,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
