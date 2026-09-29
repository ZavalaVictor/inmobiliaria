<?php

namespace App\Http\Requests\Respaldo;

use App\Enums\EstadoRespaldo;
use App\Enums\TipoRespaldo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRespaldoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['sometimes', Rule::enum(EstadoRespaldo::class)],
            'tipo' => ['sometimes', Rule::enum(TipoRespaldo::class)],
            'generado_por_user_id' => ['sometimes', 'integer', 'min:1'],
            'fecha_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_hasta' => [
                'sometimes',
                'date_format:Y-m-d H:i:s',
                Rule::when($this->filled('fecha_desde'), ['after_or_equal:fecha_desde']),
            ],
            'q' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'tipo',
                'estado',
                'tamano_bytes',
                'fecha_inicio',
                'fecha_finalizacion',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
