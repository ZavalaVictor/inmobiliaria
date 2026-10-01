<?php

namespace App\Http\Requests\Inmueble;

use App\Enums\TipoOperacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexPublicInmuebleRequest extends FormRequest
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
            'q' => ['sometimes', 'string', 'max:255'],
            'ubicacion' => ['sometimes', 'string', 'max:120'],
            'tipo_inmueble' => ['sometimes', 'string', 'max:100'],
            'tipo_operacion' => ['sometimes', Rule::enum(TipoOperacion::class)],
            'categoria_id' => [
                'sometimes',
                'integer',
                Rule::exists('categorias', 'id')->whereNull('deleted_at'),
            ],
            'municipio' => ['sometimes', 'string', 'max:100'],
            'estado_ubicacion' => ['sometimes', 'string', 'max:100'],
            'precio_min' => ['sometimes', 'numeric', 'min:0'],
            'precio_max' => ['sometimes', 'numeric', 'min:0', 'gte:precio_min'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'sort' => ['sometimes', Rule::in(['id', 'titulo', 'created_at'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
