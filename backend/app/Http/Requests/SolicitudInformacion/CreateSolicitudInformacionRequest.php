<?php

namespace App\Http\Requests\SolicitudInformacion;

use App\Enums\MedioSolicitudInformacion;
use App\Support\Authorization\ActorScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSolicitudInformacionRequest extends FormRequest
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
            'cliente_id' => [
                'sometimes',
                'nullable',
                Rule::prohibitedIf(fn (): bool => ActorScope::isClient($this->user())),
                'integer',
                Rule::exists('clientes', 'id')->whereNull('deleted_at'),
            ],
            'inmueble_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('inmuebles', 'id')->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'mensaje' => ['sometimes', 'nullable', 'string'],
            'medio_preferido' => ['sometimes', 'nullable', Rule::enum(MedioSolicitudInformacion::class)],
            ...$this->prohibitedRules(),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'atendida_por_user_id' => ['prohibited'],
            'estado' => ['prohibited'],
            'origen' => ['prohibited'],
            'fecha_solicitud' => ['prohibited'],
            'fecha_atencion' => ['prohibited'],
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
