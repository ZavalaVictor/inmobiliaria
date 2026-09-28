<?php

namespace App\Actions\Solicitudes;

use App\Enums\EstadoSolicitudInformacion;
use App\Models\Inmueble;
use App\Models\SolicitudInformacion;
use Illuminate\Validation\ValidationException;

final class CreatePublicSolicitudInformacionAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): SolicitudInformacion
    {
        $values = array_intersect_key($attributes, array_flip([
            'nombre',
            'email',
            'telefono',
            'mensaje',
            'medio_preferido',
            'inmueble_id',
        ]));

        if (array_key_exists('inmueble_id', $values) && $values['inmueble_id'] !== null) {
            $inmueble = Inmueble::query()->whereKey($values['inmueble_id'])->first();

            if ($inmueble === null || ! $inmueble->publicado) {
                throw ValidationException::withMessages([
                    'inmueble_id' => ['El Inmueble no está disponible públicamente.'],
                ]);
            }
        }

        return SolicitudInformacion::create([
            ...$values,
            'cliente_id' => null,
            'atendida_por_user_id' => null,
            'estado' => EstadoSolicitudInformacion::Nueva->value,
            'origen' => 'landing_publica',
            'fecha_atencion' => null,
        ]);
    }
}
