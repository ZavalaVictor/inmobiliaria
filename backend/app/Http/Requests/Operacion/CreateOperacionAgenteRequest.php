<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOperacionAgenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $operation = $this->route('operacion');
        $operationId = is_object($operation) ? $operation->getKey() : $operation;

        return [
            'agente_id' => [
                'required',
                'integer',
                Rule::exists('agentes', 'id')->whereNull('deleted_at'),
                Rule::unique('operacion_agentes', 'agente_id')->where(fn ($query) => $query->where('operacion_id', $operationId)),
            ],
            'es_principal' => ['sometimes', 'boolean'],
            ...$this->prohibitedRules(),
        ];
    }

    private function prohibitedRules(): array
    {
        return [
            'operacion_id' => ['prohibited'],
            'porcentaje_comision' => ['prohibited'],
            'monto_comision' => ['prohibited'],
            'comision' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'id' => ['prohibited'],
            'agente' => ['prohibited'],
            'operacion' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'tokens' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
