<?php

namespace App\Http\Requests\Operacion;

use App\Enums\EstadoOperacion;
use App\Enums\TipoOperacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_operacion' => ['sometimes', Rule::enum(TipoOperacion::class)],
            'estado' => ['sometimes', Rule::enum(EstadoOperacion::class)],
            'cliente_id' => ['sometimes', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'inmueble_id' => ['sometimes', 'integer', Rule::exists('inmuebles', 'id')->whereNull('deleted_at')],
            'oportunidad_id' => ['sometimes', 'integer', Rule::exists('oportunidades', 'id')->whereNull('deleted_at')],
            'agente_id' => ['sometimes', 'integer', Rule::exists('agentes', 'id')->whereNull('deleted_at')],
            'fecha_operacion_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_operacion_hasta' => [
                'sometimes',
                'date_format:Y-m-d H:i:s',
                Rule::when($this->filled('fecha_operacion_desde'), ['after_or_equal:fecha_operacion_desde']),
            ],
            'monto_min' => ['sometimes', 'numeric', 'decimal:0,2', 'min:0'],
            'monto_max' => [
                'sometimes',
                'numeric',
                'decimal:0,2',
                'min:0',
                Rule::when($this->filled('monto_min'), ['gte:monto_min']),
            ],
            'q' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id', 'tipo_operacion', 'monto', 'fecha_operacion', 'estado', 'created_at', 'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
