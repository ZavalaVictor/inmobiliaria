<?php

namespace App\Http\Requests\Operacion;

use App\Enums\TipoOperacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'oportunidad_id' => ['required', 'integer', Rule::exists('oportunidades', 'id')->whereNull('deleted_at')],
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'inmueble_id' => ['required', 'integer', Rule::exists('inmuebles', 'id')->whereNull('deleted_at')],
            'tipo_operacion' => ['required', Rule::enum(TipoOperacion::class)],
            'monto' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999.99'],
            'fecha_operacion' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_inicio_contrato' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'fecha_fin_contrato' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'observaciones' => ['sometimes', 'nullable', 'string'],
            ...$this->prohibitedRules(),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $start = $this->input('fecha_inicio_contrato');
            $end = $this->input('fecha_fin_contrato');

            if ($start !== null && $end !== null && $end < $start) {
                $validator->errors()->add('fecha_fin_contrato', 'La fecha final del contrato debe ser posterior o igual a la inicial.');
            }
        });
    }

    private function prohibitedRules(): array
    {
        return [
            'registrado_por_user_id' => ['prohibited'],
            'estado' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'id' => ['prohibited'],
            'agentes' => ['prohibited'],
            'asignacionesAgentes' => ['prohibited'],
            'porcentaje_comision' => ['prohibited'],
            'monto_comision' => ['prohibited'],
            'es_principal' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'tokens' => ['prohibited'],
            'oportunidad' => ['prohibited'],
            'cliente' => ['prohibited'],
            'inmueble' => ['prohibited'],
            'registradoPor' => ['prohibited'],
            'documentos' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
