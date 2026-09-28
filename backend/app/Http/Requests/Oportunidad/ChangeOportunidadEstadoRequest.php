<?php

namespace App\Http\Requests\Oportunidad;

use App\Enums\EstadoOportunidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeOportunidadEstadoRequest extends FormRequest
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
            'estado' => ['required', Rule::enum(EstadoOportunidad::class)],
            'comentario' => ['sometimes', 'nullable', 'string', 'max:500'],
            ...$this->prohibitedRules(),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'etapa' => ['prohibited'],
            'cliente_id' => ['prohibited'],
            'inmueble_id' => ['prohibited'],
            'agente_principal_id' => ['prohibited'],
            'solicitud_informacion_id' => ['prohibited'],
            'titulo' => ['prohibited'],
            'notas' => ['prohibited'],
            'fecha_apertura' => ['prohibited'],
            'fecha_cierre' => ['prohibited'],
            'motivo_perdida' => ['prohibited'],
            'cambiado_por_user_id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'historial' => ['prohibited'],
            'citas' => ['prohibited'],
            'operacion' => ['prohibited'],
            'roles' => ['prohibited'],
            'permisos' => ['prohibited'],
            'permissions' => ['prohibited'],
            'tokens' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
