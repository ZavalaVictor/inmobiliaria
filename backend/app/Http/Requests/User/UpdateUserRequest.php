<?php

namespace App\Http\Requests\User;

use App\Enums\EstadoUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        return [
            'nombres' => ['sometimes', 'required', 'string', 'max:100'],
            'apellido_paterno' => ['sometimes', 'required', 'string', 'max:80'],
            'apellido_materno' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'estado' => ['sometimes', Rule::enum(EstadoUsuario::class)],
            'password' => ['prohibited'],
            'password_confirmation' => ['prohibited'],
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
            'remember_token' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'last_login_at' => ['prohibited'],
            'roles' => ['prohibited'],
            'role' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'cliente' => ['prohibited'],
            'agente' => ['prohibited'],
            'cliente_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
