<?php

namespace App\Http\Requests\VisualizacionInmueble;

use Illuminate\Foundation\Http\FormRequest;

class RecordVisualizacionInmuebleRequest extends FormRequest
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
            'inmueble_id' => ['prohibited'],
            'session_id' => ['prohibited'],
            'ip_hash' => ['prohibited'],
            'ip' => ['prohibited'],
            'user_agent' => ['prohibited'],
            'referer' => ['prohibited'],
            'origen' => ['prohibited'],
            'fecha_visualizacion' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'cliente_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
        ];
    }
}
