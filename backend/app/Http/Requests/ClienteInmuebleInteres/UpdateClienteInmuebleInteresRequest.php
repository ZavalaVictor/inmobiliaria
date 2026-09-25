<?php

namespace App\Http\Requests\ClienteInmuebleInteres;

use App\Enums\EstadoInteresInmueble;
use App\Enums\NivelInteres;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteInmuebleInteresRequest extends FormRequest
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
            'nivel_interes' => ['sometimes', 'nullable', Rule::enum(NivelInteres::class)],
            'estado' => ['sometimes', Rule::enum(EstadoInteresInmueble::class)],
            'notas' => ['sometimes', 'nullable', 'string'],
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
            'fecha_interes' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'cliente' => ['prohibited'],
            'inmueble' => ['prohibited'],
            'roles' => ['prohibited'],
            'role' => ['prohibited'],
            'permisos' => ['prohibited'],
            'permissions' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'agentes' => ['prohibited'],
            'clientes' => ['prohibited'],
            'intereses' => ['prohibited'],
            'operaciones' => ['prohibited'],
            'citas' => ['prohibited'],
            'oportunidades' => ['prohibited'],
            'documentos' => ['prohibited'],
            'imagenes' => ['prohibited'],
        ];
    }
}
