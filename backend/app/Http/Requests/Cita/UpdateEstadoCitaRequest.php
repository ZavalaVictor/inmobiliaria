<?php

namespace App\Http\Requests\Cita;

use App\Enums\EstadoCita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstadoCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoCita::class)],
            'cliente_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'inmueble_id' => ['prohibited'],
            'oportunidad_id' => ['prohibited'],
            'creado_por_user_id' => ['prohibited'],
            'fecha_inicio' => ['prohibited'],
            'fecha_fin' => ['prohibited'],
            'motivo' => ['prohibited'],
            'notas' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'historial' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
