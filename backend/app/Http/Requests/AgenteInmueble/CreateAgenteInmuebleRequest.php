<?php

namespace App\Http\Requests\AgenteInmueble;

use App\Models\Inmueble;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAgenteInmuebleRequest extends FormRequest
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
        $inmueble = $this->route('inmueble');
        $inmuebleId = $inmueble instanceof Inmueble ? $inmueble->getKey() : $inmueble;

        return [
            'agente_id' => [
                'required',
                'integer',
                Rule::exists('agentes', 'id')->whereNull('deleted_at'),
                Rule::unique('agente_inmueble', 'agente_id')
                    ->where(fn ($query) => $query->where('inmueble_id', $inmuebleId)),
            ],
            'es_principal' => ['sometimes', 'boolean'],
            ...$this->prohibitedRules(),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'inmueble_id' => ['prohibited'],
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
