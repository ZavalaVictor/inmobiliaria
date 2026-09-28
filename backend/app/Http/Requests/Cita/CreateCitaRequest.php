<?php

namespace App\Http\Requests\Cita;

use App\Support\Authorization\ActorScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'agente_id' => [
                Rule::requiredIf(fn (): bool => ! ActorScope::isAgent($this->user())),
                Rule::prohibitedIf(fn (): bool => ActorScope::isAgent($this->user())),
                'sometimes',
                'integer',
                Rule::exists('agentes', 'id')->whereNull('deleted_at'),
            ],
            'inmueble_id' => ['required', 'integer', Rule::exists('inmuebles', 'id')->whereNull('deleted_at')],
            'oportunidad_id' => ['sometimes', 'nullable', 'integer', Rule::exists('oportunidades', 'id')->whereNull('deleted_at')],
            'fecha_inicio' => ['required', 'date_format:Y-m-d H:i:s'],
            'fecha_fin' => ['required', 'date_format:Y-m-d H:i:s', 'after:fecha_inicio'],
            'motivo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notas' => ['sometimes', 'nullable', 'string'],
            ...$this->prohibitedRules(),
        ];
    }

    private function prohibitedRules(): array
    {
        return [
            'creado_por_user_id' => ['prohibited'],
            'estado' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'historial' => ['prohibited'],
            'historialCorreos' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'cliente' => ['prohibited'],
            'agente' => ['prohibited'],
            'inmueble' => ['prohibited'],
            'oportunidad' => ['prohibited'],
            'creadoPor' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
