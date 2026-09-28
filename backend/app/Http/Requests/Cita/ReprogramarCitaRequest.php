<?php

namespace App\Http\Requests\Cita;

use App\Support\Authorization\ActorScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReprogramarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => ['required', 'date_format:Y-m-d H:i:s'],
            'fecha_fin' => ['required', 'date_format:Y-m-d H:i:s', 'after:fecha_inicio'],
            'agente_id' => [
                'sometimes',
                'integer',
                Rule::prohibitedIf(fn (): bool => ActorScope::isAgent($this->user())),
                Rule::exists('agentes', 'id')->whereNull('deleted_at'),
            ],
            'motivo' => ['required', 'string', 'max:500'],
            ...$this->prohibitedRules(),
        ];
    }

    private function prohibitedRules(): array
    {
        return [
            'cliente_id' => ['prohibited'],
            'inmueble_id' => ['prohibited'],
            'oportunidad_id' => ['prohibited'],
            'creado_por_user_id' => ['prohibited'],
            'estado' => ['prohibited'],
            'tipo_cambio' => ['prohibited'],
            'modificado_por_user_id' => ['prohibited'],
            'agente_anterior_id' => ['prohibited'],
            'agente_nuevo_id' => ['prohibited'],
            'fecha_inicio_anterior' => ['prohibited'],
            'fecha_fin_anterior' => ['prohibited'],
            'fecha_inicio_nueva' => ['prohibited'],
            'fecha_fin_nueva' => ['prohibited'],
            'fecha_modificacion' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'historial' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
