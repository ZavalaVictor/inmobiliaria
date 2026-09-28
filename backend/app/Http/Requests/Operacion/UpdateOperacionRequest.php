<?php

namespace App\Http\Requests\Operacion;

use App\Enums\EstadoOperacion;
use App\Models\Operacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['sometimes', Rule::enum(EstadoOperacion::class)],
            'fecha_inicio_contrato' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'fecha_fin_contrato' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'observaciones' => ['sometimes', 'nullable', 'string'],
            ...$this->prohibitedRules(),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $operation = $this->route('operacion');
            $start = $this->input('fecha_inicio_contrato', $operation instanceof Operacion ? $operation->fecha_inicio_contrato?->format('Y-m-d') : null);
            $end = $this->input('fecha_fin_contrato', $operation instanceof Operacion ? $operation->fecha_fin_contrato?->format('Y-m-d') : null);

            if ($start !== null && $end !== null && $end < $start) {
                $validator->errors()->add('fecha_fin_contrato', 'La fecha final del contrato debe ser posterior o igual a la inicial.');
            }
        });
    }

    private function prohibitedRules(): array
    {
        return [
            'oportunidad_id' => ['prohibited'],
            'cliente_id' => ['prohibited'],
            'inmueble_id' => ['prohibited'],
            'registrado_por_user_id' => ['prohibited'],
            'tipo_operacion' => ['prohibited'],
            'monto' => ['prohibited'],
            'fecha_operacion' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'agentes' => ['prohibited'],
            'asignacionesAgentes' => ['prohibited'],
            'comisiones' => ['prohibited'],
            'porcentaje_comision' => ['prohibited'],
            'monto_comision' => ['prohibited'],
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
