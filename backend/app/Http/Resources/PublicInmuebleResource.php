<?php

namespace App\Http\Resources;

use App\Enums\EstadoDisponibilidadInmueble;
use App\Enums\TipoOperacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PublicInmuebleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $operation = $this->tipo_operacion instanceof TipoOperacion
            ? $this->tipo_operacion->value
            : $this->tipo_operacion;
        $price = $operation === 'venta' ? $this->precio_venta : $this->renta_mensual;

        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'titulo' => $this->titulo,
            'slug' => $this->slug,
            'descripcion' => $this->descripcion,
            'tipo_operacion' => $operation,
            'precio' => $price === null ? null : (float) $price,
            'moneda' => 'MXN',
            'municipio' => $this->municipio,
            'estado_ubicacion' => $this->estado_ubicacion,
            'coordenadas' => $this->publicCoordinates(),
            'habitaciones' => $this->habitaciones,
            'banos_completos' => $this->banos_completos,
            'medios_banos' => $this->medios_banos,
            'estacionamientos' => $this->estacionamientos,
            'niveles' => $this->niveles,
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
            'imagen_principal' => $this->whenLoaded('imagenPrincipal', function (): ?array {
                return $this->imagenPrincipal === null ? null : [
                    'url_publica' => $this->imagenPrincipal->url_publica,
                    'texto_alternativo' => $this->imagenPrincipal->texto_alternativo,
                ];
            }),
            'imagenes' => $this->whenLoaded('imagenes', function (): array {
                return $this->imagenes
                    ->sortBy('orden')
                    ->values()
                    ->map(static fn ($image): array => [
                        'id' => $image->id,
                        'url_publica' => $image->url_publica,
                        'texto_alternativo' => $image->texto_alternativo,
                        'es_principal' => (bool) $image->es_principal,
                        'orden' => $image->orden,
                    ])
                    ->all();
            }),
        ];
    }

    /**
     * Expose an approximate map point instead of the exact stored coordinates.
     * Three decimals keep the public experience useful while reducing address precision.
     *
     * @return array{latitud: float, longitud: float}|null
     */
    private function publicCoordinates(): ?array
    {
        if ($this->latitud === null || $this->longitud === null) {
            return null;
        }

        return [
            'latitud' => round((float) $this->latitud, 3),
            'longitud' => round((float) $this->longitud, 3),
        ];
    }
}
