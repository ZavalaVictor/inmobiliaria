<?php

namespace App\Http\Requests\Oportunidad;

use App\Support\Authorization\ActorScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOportunidadRequest extends FormRequest
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
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->whereNull('deleted_at'),
            ],
            'inmueble_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('inmuebles', 'id')->whereNull('deleted_at'),
            ],
            'agente_principal_id' => [
                'sometimes',
                'nullable',
                Rule::prohibitedIf(fn (): bool => ActorScope::isAgent($this->user())),
                'integer',
                Rule::exists('agentes', 'id')->whereNull('deleted_at'),
            ],
            'solicitud_informacion_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('solicitudes_informacion', 'id')->whereNull('deleted_at'),
            ],
            'titulo' => ['required', 'string', 'max:150'],
            'notas' => ['sometimes', 'nullable', 'string'],
            ...$this->prohibitedRules(),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'etapa' => ['prohibited'],
            'estado' => ['prohibited'],
            'fecha_apertura' => ['prohibited'],
            'fecha_cierre' => ['prohibited'],
            'motivo_perdida' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'historial' => ['prohibited'],
            'citas' => ['prohibited'],
            'operacion' => ['prohibited'],
            'cambiado_por_user_id' => ['prohibited'],
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
