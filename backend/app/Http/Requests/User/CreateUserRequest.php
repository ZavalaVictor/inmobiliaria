<?php

namespace App\Http\Requests\User;

use App\Enums\EstadoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
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
            'nombres' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:80'],
            'apellido_materno' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required', 'string'],
            'estado' => ['sometimes', Rule::enum(EstadoUsuario::class)],
            ...$this->prohibitedRules(),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'id' => ['prohibited'],
            'roles' => ['prohibited'],
            'role' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'cliente' => ['prohibited'],
            'agente' => ['prohibited'],
            'cliente_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'remember_token' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'last_login_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
