<?php

namespace App\Http\Requests\Oportunidad;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOportunidadRequest extends FormRequest
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
            'titulo' => ['sometimes', 'string', 'max:150'],
            'notas' => ['sometimes', 'nullable', 'string'],
            'fecha_cierre' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'motivo_perdida' => ['sometimes', 'nullable', 'string', 'max:255'],
            ...$this->prohibitedRules(),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'cliente_id' => ['prohibited'],
            'inmueble_id' => ['prohibited'],
            'agente_principal_id' => ['prohibited'],
            'solicitud_informacion_id' => ['prohibited'],
            'etapa' => ['prohibited'],
            'estado' => ['prohibited'],
            'fecha_apertura' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'historial' => ['prohibited'],
            'citas' => ['prohibited'],
            'operacion' => ['prohibited'],
            'roles' => ['prohibited'],
            'permisos' => ['prohibited'],
            'permissions' => ['prohibited'],
            'tokens' => ['prohibited'],
            'cliente' => ['prohibited'],
            'inmueble' => ['prohibited'],
            'agentePrincipal' => ['prohibited'],
            'solicitudInformacion' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
