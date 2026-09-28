<?php

namespace App\Http\Requests\SolicitudInformacion;

use App\Enums\EstadoSolicitudInformacion;
use App\Enums\MedioSolicitudInformacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSolicitudInformacionRequest extends FormRequest
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
            'estado' => ['sometimes', Rule::enum(EstadoSolicitudInformacion::class)],
            'medio_preferido' => ['sometimes', Rule::enum(MedioSolicitudInformacion::class)],
            'origen' => ['sometimes', 'string', 'max:30'],
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
            'atendida_por_user_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'fecha_solicitud_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_solicitud_hasta' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_atencion_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_atencion_hasta' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'q' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'nombre',
                'email',
                'estado',
                'origen',
                'fecha_solicitud',
                'fecha_atencion',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
