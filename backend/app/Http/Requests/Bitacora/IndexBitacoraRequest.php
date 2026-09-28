<?php

namespace App\Http\Requests\Bitacora;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBitacoraRequest extends FormRequest
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
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'accion' => ['sometimes', 'string', 'max:50'],
            'entidad' => ['sometimes', 'string', 'max:80'],
            'entidad_id' => ['sometimes', 'integer', 'min:1'],
            'fecha_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_hasta' => [
                'sometimes',
                'date_format:Y-m-d H:i:s',
                Rule::when($this->filled('fecha_desde'), ['after_or_equal:fecha_desde']),
            ],
            'q' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'user_id',
                'accion',
                'entidad',
                'entidad_id',
                'fecha_evento',
                'created_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
