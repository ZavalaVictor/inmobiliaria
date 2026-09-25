<?php

namespace App\Http\Requests\InteraccionCliente;

use App\Enums\TipoInteraccionCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInteraccionClienteRequest extends FormRequest
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
            'tipo' => ['sometimes', Rule::enum(TipoInteraccionCliente::class)],
            'descripcion' => ['sometimes', 'string'],
            'resultado' => ['sometimes', 'nullable', 'string', 'max:255'],
            'proxima_accion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fecha_proxima_accion' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
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
            'registrado_por_user_id' => ['prohibited'],
            'fecha_interaccion' => ['prohibited'],
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'cliente' => ['prohibited'],
            'registradoPor' => ['prohibited'],
            'registrado_por' => ['prohibited'],
            'user' => ['prohibited'],
            'roles' => ['prohibited'],
            'role' => ['prohibited'],
            'permisos' => ['prohibited'],
            'permissions' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'agentes' => ['prohibited'],
            'inmuebles' => ['prohibited'],
            'intereses' => ['prohibited'],
            'oportunidades' => ['prohibited'],
            'solicitudes' => ['prohibited'],
            'citas' => ['prohibited'],
            'operaciones' => ['prohibited'],
            'documentos' => ['prohibited'],
        ];
    }
}
