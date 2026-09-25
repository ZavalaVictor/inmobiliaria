<?php

namespace App\Http\Requests\AgenteInmueble;

use Illuminate\Foundation\Http\FormRequest;

class SetPrincipalAgenteInmuebleRequest extends FormRequest
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
            'agente_id' => ['prohibited'],
            'inmueble_id' => ['prohibited'],
            'es_principal' => ['prohibited'],
            'fecha_asignacion' => ['prohibited'],
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'agente' => ['prohibited'],
            'inmueble' => ['prohibited'],
            'user' => ['prohibited'],
            'roles' => ['prohibited'],
            'permisos' => ['prohibited'],
            'permissions' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'agentes' => ['prohibited'],
            'clientes' => ['prohibited'],
            'operaciones' => ['prohibited'],
            'imagenes' => ['prohibited'],
            'documentos' => ['prohibited'],
            'intereses' => ['prohibited'],
        ];
    }
}
