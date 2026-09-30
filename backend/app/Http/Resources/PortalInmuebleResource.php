<?php

namespace App\Http\Resources;

use App\Enums\EstadoDisponibilidadInmueble;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PortalInmuebleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isSale = ($this->tipo_operacion?->value ?? $this->tipo_operacion) === 'venta';
        $price = $isSale ? $this->precio_venta : $this->renta_mensual;

        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'titulo' => $this->titulo,
            'slug' => $this->slug,
            'tipo_operacion' => $this->tipo_operacion?->value ?? $this->tipo_operacion,
            'precio' => $price === null ? null : (float) $price,
            'moneda' => 'MXN',
            'municipio' => $this->municipio,
            'estado_ubicacion' => $this->estado_ubicacion,
            'habitaciones' => $this->habitaciones,
            'banos_completos' => $this->banos_completos,
            'superficie_terreno_m2' => $this->superficie_terreno_m2,
            'superficie_construccion_m2' => $this->superficie_construccion_m2,
            'estado_disponibilidad' => $this->estado_disponibilidad instanceof EstadoDisponibilidadInmueble
                ? $this->estado_disponibilidad->value
                : $this->estado_disponibilidad,
            'categoria' => $this->whenLoaded('categoria', function (): ?array {
                return $this->categoria === null ? null : [
                    'id' => $this->categoria->id,
                    'nombre' => $this->categoria->nombre,
                ];
            }),
        ];
    }
}
