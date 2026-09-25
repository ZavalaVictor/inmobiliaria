<?php

namespace App\Http\Requests\Propietario;

use App\Enums\EstadoRegistroPropietario;
use App\Enums\TipoPersonaPropietario;
use App\Models\Propietario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPropietarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can('viewAny', Propietario::class);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'tipo_persona' => ['sometimes', Rule::enum(TipoPersonaPropietario::class)],
            'estado_registro' => ['sometimes', Rule::enum(EstadoRegistroPropietario::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'nombre_razon_social',
                'rfc',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
