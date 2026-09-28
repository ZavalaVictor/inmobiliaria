<?php

namespace App\Http\Requests\Oportunidad;

use App\Enums\EtapaOportunidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeOportunidadEtapaRequest extends FormRequest
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
            'etapa' => ['required', Rule::enum(EtapaOportunidad::class)],
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
            'estado' => ['prohibited'],
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
