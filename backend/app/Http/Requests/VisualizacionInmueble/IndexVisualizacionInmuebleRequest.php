<?php

namespace App\Http\Requests\VisualizacionInmueble;

use App\Enums\OrigenVisualizacionInmueble;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexVisualizacionInmuebleRequest extends FormRequest
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
            'inmueble_id' => ['sometimes', 'integer', 'min:1'],
            'origen' => ['sometimes', Rule::enum(OrigenVisualizacionInmueble::class)],
            'fecha_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_hasta' => [
                'sometimes',
                'date_format:Y-m-d H:i:s',
                Rule::when($this->filled('fecha_desde'), ['after_or_equal:fecha_desde']),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'inmueble_id',
                'origen',
                'fecha_visualizacion',
                'created_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
