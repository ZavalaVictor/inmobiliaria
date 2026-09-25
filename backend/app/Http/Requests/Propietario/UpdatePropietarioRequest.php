<?php

namespace App\Http\Requests\Propietario;

use App\Enums\EstadoRegistroPropietario;
use App\Enums\TipoPersonaPropietario;
use App\Models\Propietario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropietarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $propietario = $this->route('propietario');

        return $this->user() !== null
            && $propietario instanceof Propietario
            && $this->user()->can('update', $propietario);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $propietario = $this->route('propietario');
        $uniqueRfc = Rule::unique('propietarios', 'rfc');

        if ($propietario instanceof Propietario) {
            $uniqueRfc->ignore($propietario->getKey());
        }

        return [
            'tipo_persona' => ['sometimes', Rule::enum(TipoPersonaPropietario::class)],
            'nombre_razon_social' => ['sometimes', 'required', 'string', 'max:150'],
            'rfc' => ['sometimes', 'required', 'string', 'max:13', $uniqueRfc],
            'telefono' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'direccion' => ['sometimes', 'required', 'string', 'max:255'],
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
