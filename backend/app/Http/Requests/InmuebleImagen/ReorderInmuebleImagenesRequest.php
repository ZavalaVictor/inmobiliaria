<?php

namespace App\Http\Requests\InmuebleImagen;

use Illuminate\Foundation\Http\FormRequest;

class ReorderInmuebleImagenesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'imagenes' => ['required', 'array'],
            'imagenes.*' => ['integer', 'distinct'],
            'inmueble_id' => ['prohibited'],
            'firebase_path' => ['prohibited'],
            'url_publica' => ['prohibited'],
            'nombre_original' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'tamano_bytes' => ['prohibited'],
            'es_principal' => ['prohibited'],
            'orden' => ['prohibited'],
            'texto_alternativo' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
