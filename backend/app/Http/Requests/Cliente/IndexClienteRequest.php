<?php

namespace App\Http\Requests\Cliente;

use App\Enums\EstadoCliente;
use App\Enums\TipoInteresCliente;
use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can('viewAny', Cliente::class);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:150'],
            'estado_cliente' => ['sometimes', Rule::enum(EstadoCliente::class)],
            'tipo_interes' => ['sometimes', Rule::enum(TipoInteresCliente::class)],
            'agente_id' => ['sometimes', 'integer', 'exists:agentes,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'nombres',
                'apellido_paterno',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
