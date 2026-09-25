<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRolesRequest extends FormRequest
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
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'distinct', Rule::in($this->approvedRoles())],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'guard_name' => ['prohibited'],
            'model_has_roles' => ['prohibited'],
            'role_ids' => ['prohibited'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function approvedRoles(): array
    {
        return [
            'Administrador',
            'Agente Inmobiliario',
            'Asistente',
            'Director General',
            'Cliente',
        ];
    }
}
