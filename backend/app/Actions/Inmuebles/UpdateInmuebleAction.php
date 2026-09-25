<?php

namespace App\Actions\Inmuebles;

use App\Models\Inmueble;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Support\Arr;

final class UpdateInmuebleAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, Inmueble $inmueble, array $attributes): Inmueble
    {
        $allowed = [
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
        ];

        if (! ActorScope::isAgent($user)) {
            $allowed[] = 'propietario_id';
            $allowed[] = 'categoria_id';
        }

        $inmueble->fill(Arr::only($attributes, $allowed));
        $inmueble->save();

        return $inmueble->fresh();
    }
}
