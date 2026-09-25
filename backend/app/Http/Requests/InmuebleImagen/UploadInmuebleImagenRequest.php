<?php

namespace App\Http\Requests\InmuebleImagen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadInmuebleImagenRequest extends FormRequest
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
            'imagen' => [
                'required',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(
                    (int) config('services.firebase.inmueble_image_max_kb', 10240)
                ),
            ],
            'texto_alternativo' => ['sometimes', 'nullable', 'string', 'max:180'],
            'inmueble_id' => ['prohibited'],
            'firebase_path' => ['prohibited'],
            'url_publica' => ['prohibited'],
            'nombre_original' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'tamano_bytes' => ['prohibited'],
            'es_principal' => ['prohibited'],
            'orden' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
