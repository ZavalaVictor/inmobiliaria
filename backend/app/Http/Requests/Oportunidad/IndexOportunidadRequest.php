<?php

namespace App\Http\Requests\Oportunidad;

use App\Enums\EstadoOportunidad;
use App\Enums\EtapaOportunidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOportunidadRequest extends FormRequest
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
            'etapa' => ['sometimes', Rule::enum(EtapaOportunidad::class)],
            'estado' => ['sometimes', Rule::enum(EstadoOportunidad::class)],
            'cliente_id' => [
                'sometimes',
                'integer',
                Rule::exists('clientes', 'id')->whereNull('deleted_at'),
            ],
            'inmueble_id' => [
                'sometimes',
                'integer',
                Rule::exists('inmuebles', 'id')->whereNull('deleted_at'),
            ],
            'agente_principal_id' => [
                'sometimes',
                'integer',
                Rule::exists('agentes', 'id')->whereNull('deleted_at'),
            ],
            'solicitud_informacion_id' => [
                'sometimes',
                'integer',
                Rule::exists('solicitudes_informacion', 'id')->whereNull('deleted_at'),
            ],
            'fecha_apertura_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_apertura_hasta' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_cierre_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_cierre_hasta' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'q' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'titulo',
                'etapa',
                'estado',
                'fecha_apertura',
                'fecha_cierre',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
