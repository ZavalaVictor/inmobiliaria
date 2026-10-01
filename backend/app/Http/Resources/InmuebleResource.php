<?php

namespace App\Http\Resources;

use App\Enums\EstadoDisponibilidadInmueble;
use App\Enums\TipoOperacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InmuebleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'titulo' => $this->titulo,
            'slug' => $this->slug,
            'descripcion' => $this->descripcion,
            'tipo_operacion' => $this->tipo_operacion instanceof TipoOperacion
                ? $this->tipo_operacion->value
                : $this->tipo_operacion,
            'precio_venta' => $this->precio_venta,
            'renta_mensual' => $this->renta_mensual,
            'superficie_terreno_m2' => $this->superficie_terreno_m2,
            'superficie_construccion_m2' => $this->superficie_construccion_m2,
            'habitaciones' => $this->habitaciones,
            'banos_completos' => $this->banos_completos,
            'medios_banos' => $this->medios_banos,
            'estacionamientos' => $this->estacionamientos,
            'niveles' => $this->niveles,
            'calle' => $this->calle,
            'numero_exterior' => $this->numero_exterior,
            'numero_interior' => $this->numero_interior,
            'colonia' => $this->colonia,
            'municipio' => $this->municipio,
            'estado_ubicacion' => $this->estado_ubicacion,
            'codigo_postal' => $this->codigo_postal,
            'referencias' => $this->referencias,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'estado_disponibilidad' => $this->estado_disponibilidad instanceof EstadoDisponibilidadInmueble
                ? $this->estado_disponibilidad->value
                : $this->estado_disponibilidad,
            'publicado' => $this->publicado,
            'fecha_publicacion' => $this->fecha_publicacion?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'categoria' => $this->whenLoaded('categoria', function (): array {
                return [
                    'id' => $this->categoria->id,
                    'nombre' => $this->categoria->nombre,
                ];
            }),
            'imagen_principal' => $this->when(
                $this->relationLoaded('imagenPrincipal'),
                fn (): ?array => $this->imagenPrincipal === null
                    ? null
                    : (new InmuebleImagenResource($this->imagenPrincipal))->toArray($request),
            ),
            'imagenes' => InmuebleImagenResource::collection($this->whenLoaded('imagenes')),
        ];

        if ($request->user()?->can('propietarios.ver')) {
            $data['propietario_id'] = $this->propietario_id;
            $data['propietario'] = $this->whenLoaded('propietario', function (): array {
                return [
                    'id' => $this->propietario->id,
                    'nombre_razon_social' => $this->propietario->nombre_razon_social,
                ];
            });
        }

        return $data;
    }
}
