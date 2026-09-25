<?php

namespace App\Http\Requests\Agente;

use App\Enums\EstadoLaboralAgente;
use App\Models\Agente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgenteRequest extends FormRequest
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
        /** @var Agente $agente */
        $agente = $this->route('agente');

        return [
            'numero_empleado' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('agentes', 'numero_empleado')->ignore($agente->getKey()),
            ],
            'telefono_corporativo' => ['sometimes', 'nullable', 'string', 'max:20'],
            'zona_asignacion' => ['sometimes', 'nullable', 'string', 'max:100'],
            'horario' => ['sometimes', 'nullable', 'string'],
            'porcentaje_comision' => ['sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'estado_laboral' => ['sometimes', Rule::enum(EstadoLaboralAgente::class)],
            'fecha_contratacion' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'user_id' => ['prohibited'],
            ...$this->prohibitedRules(),
        ];
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
