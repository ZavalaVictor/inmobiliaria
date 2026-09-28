<?php

namespace App\Http\Requests\Cita;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notas' => ['sometimes', 'nullable', 'string'],
            ...$this->prohibitedRules(),
        ];
    }

    private function prohibitedRules(): array
    {
        return [
            'cliente_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'inmueble_id' => ['prohibited'],
            'oportunidad_id' => ['prohibited'],
            'creado_por_user_id' => ['prohibited'],
            'fecha_inicio' => ['prohibited'],
            'fecha_fin' => ['prohibited'],
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
