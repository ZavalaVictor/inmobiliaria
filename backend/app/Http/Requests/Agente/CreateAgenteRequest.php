<?php

namespace App\Http\Requests\Agente;

use App\Enums\EstadoLaboralAgente;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateAgenteRequest extends FormRequest
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
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::unique('agentes', 'user_id'),
            ],
            'numero_empleado' => ['required', 'string', 'max:30', Rule::unique('agentes', 'numero_empleado')],
            'telefono_corporativo' => ['sometimes', 'nullable', 'string', 'max:20'],
            'zona_asignacion' => ['sometimes', 'nullable', 'string', 'max:100'],
            'horario' => ['sometimes', 'nullable', 'string'],
            'porcentaje_comision' => ['sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'estado_laboral' => ['sometimes', Rule::enum(EstadoLaboralAgente::class)],
            'fecha_contratacion' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            ...$this->prohibitedRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('user_id')) {
                return;
            }

            $user = User::query()
                ->whereKey($this->integer('user_id'))
                ->whereNull('deleted_at')
                ->first();

            if ($user !== null && ! $user->hasRole('Agente Inmobiliario')) {
                $validator->errors()->add('user_id', 'El usuario debe tener el rol Agente Inmobiliario.');
            }
        });
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'id' => ['prohibited'],
            'foto_path' => ['prohibited'],
            'user' => ['prohibited'],
            'inmuebles' => ['prohibited'],
            'clientes' => ['prohibited'],
            'operaciones' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'asignaciones_inmuebles' => ['prohibited'],
            'asignaciones_clientes' => ['prohibited'],
            'asignaciones_operaciones' => ['prohibited'],
            'roles' => ['prohibited'],
            'permisos' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
