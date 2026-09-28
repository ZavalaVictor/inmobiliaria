<?php

namespace App\Http\Requests\SolicitudInformacion;

use App\Enums\EstadoSolicitudInformacion;
use App\Enums\MedioSolicitudInformacion;
use App\Support\Authorization\ActorScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSolicitudInformacionRequest extends FormRequest
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
        $isAgent = ActorScope::isAgent($this->user());

        $rules = $isAgent
            ? [
                'estado' => ['sometimes', Rule::enum(EstadoSolicitudInformacion::class)],
                'fecha_atencion' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
                'nombre' => ['prohibited'],
                'email' => ['prohibited'],
                'telefono' => ['prohibited'],
                'mensaje' => ['prohibited'],
                'medio_preferido' => ['prohibited'],
                'atendida_por_user_id' => ['prohibited'],
            ]
            : [
                'nombre' => ['sometimes', 'string', 'max:150'],
                'email' => ['sometimes', 'email', 'max:150'],
                'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
                'mensaje' => ['sometimes', 'nullable', 'string'],
                'medio_preferido' => ['sometimes', 'nullable', Rule::enum(MedioSolicitudInformacion::class)],
                'estado' => ['sometimes', Rule::enum(EstadoSolicitudInformacion::class)],
                'atendida_por_user_id' => [
                    'sometimes',
                    'nullable',
                    'integer',
                    Rule::exists('users', 'id')->whereNull('deleted_at'),
                ],
                'fecha_atencion' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            ];

        return [
            ...$rules,
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
            'inmueble_id' => ['prohibited'],
            'origen' => ['prohibited'],
            'fecha_solicitud' => ['prohibited'],
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'user_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'roles' => ['prohibited'],
            'role' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'cliente' => ['prohibited'],
            'inmueble' => ['prohibited'],
            'atendidaPor' => ['prohibited'],
            'usuario' => ['prohibited'],
            'relaciones' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'intereses' => ['prohibited'],
            'agentes' => ['prohibited'],
            'oportunidades' => ['prohibited'],
            'citas' => ['prohibited'],
            'operaciones' => ['prohibited'],
        ];
    }
}
