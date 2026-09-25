<?php

namespace App\Http\Requests\ClienteAgente;

use Illuminate\Foundation\Http\FormRequest;

class SetPrincipalClienteAgenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'es_principal' => ['prohibited'],
            'fecha_asignacion' => ['prohibited'],
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'cliente' => ['prohibited'],
            'agente' => ['prohibited'],
            'user' => ['prohibited'],
            'roles' => ['prohibited'],
            'role' => ['prohibited'],
            'permisos' => ['prohibited'],
            'permissions' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'agentes' => ['prohibited'],
            'clientes' => ['prohibited'],
            'inmuebles' => ['prohibited'],
            'intereses' => ['prohibited'],
            'operaciones' => ['prohibited'],
            'citas' => ['prohibited'],
            'oportunidades' => ['prohibited'],
        ];
    }
}
