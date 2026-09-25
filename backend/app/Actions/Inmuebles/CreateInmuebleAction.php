<?php

namespace App\Actions\Inmuebles;

use App\Models\Inmueble;
use Illuminate\Support\Arr;

final class CreateInmuebleAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Inmueble
    {
        return Inmueble::create(Arr::only($attributes, [
            'propietario_id',
            'categoria_id',
            'codigo',
            'titulo',
            'slug',
            'descripcion',
            'tipo_operacion',
            'precio_venta',
            'renta_mensual',
            'superficie_terreno_m2',
            'superficie_construccion_m2',
            'habitaciones',
            'banos_completos',
            'medios_banos',
            'estacionamientos',
            'niveles',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'municipio',
            'estado_ubicacion',
            'codigo_postal',
            'referencias',
            'latitud',
            'longitud',
            'estado_disponibilidad',
            'publicado',
            'fecha_publicacion',
        ]));
    }
}
