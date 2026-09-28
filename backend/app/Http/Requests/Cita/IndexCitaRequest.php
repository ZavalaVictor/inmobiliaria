<?php

namespace App\Http\Requests\Cita;

use App\Enums\EstadoCita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['sometimes', Rule::enum(EstadoCita::class)],
            'agente_id' => ['sometimes', 'integer', Rule::exists('agentes', 'id')->whereNull('deleted_at')],
            'cliente_id' => ['sometimes', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'inmueble_id' => ['sometimes', 'integer', Rule::exists('inmuebles', 'id')->whereNull('deleted_at')],
            'oportunidad_id' => ['sometimes', 'integer', Rule::exists('oportunidades', 'id')->whereNull('deleted_at')],
            'fecha_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_hasta' => [
                'sometimes',
                'date_format:Y-m-d H:i:s',
                Rule::when($this->filled('fecha_desde'), ['after:fecha_desde']),
            ],
            'q' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in(['id', 'fecha_inicio', 'fecha_fin', 'estado', 'created_at', 'updated_at'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
