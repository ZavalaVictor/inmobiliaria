<?php

namespace App\Http\Requests\Propietario;

use App\Enums\EstadoRegistroPropietario;
use App\Enums\TipoPersonaPropietario;
use App\Models\Propietario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePropietarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can('create', Propietario::class);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo_persona' => ['sometimes', Rule::enum(TipoPersonaPropietario::class)],
            'nombre_razon_social' => ['required', 'string', 'max:150'],
            'rfc' => ['required', 'string', 'max:13', Rule::unique('propietarios', 'rfc')],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],
            'estado_registro' => ['sometimes', Rule::enum(EstadoRegistroPropietario::class)],
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
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'inmuebles' => ['prohibited'],
            'relaciones' => ['prohibited'],
            'roles' => ['prohibited'],
            'permisos' => ['prohibited'],
        ];
    }
}
