<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;

class SetPrincipalOperacionAgenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operacion_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'es_principal' => ['prohibited'],
            'porcentaje_comision' => ['prohibited'],
            'monto_comision' => ['prohibited'],
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
