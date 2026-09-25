<?php

namespace App\Http\Requests\User;

use App\Enums\EstadoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
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
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'estado' => ['sometimes', Rule::enum(EstadoUsuario::class)],
            'role' => ['sometimes', Rule::in($this->approvedRoles())],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'nombres',
                'apellido_paterno',
                'email',
                'estado',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
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
