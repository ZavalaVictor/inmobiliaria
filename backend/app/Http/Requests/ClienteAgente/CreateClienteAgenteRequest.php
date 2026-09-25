<?php

namespace App\Http\Requests\ClienteAgente;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateClienteAgenteRequest extends FormRequest
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
        $cliente = $this->route('cliente');
        $clienteId = $cliente instanceof Cliente ? $cliente->getKey() : $cliente;

        return [
            'agente_id' => [
                'required',
                'integer',
                Rule::exists('agentes', 'id')->whereNull('deleted_at'),
                Rule::unique('cliente_agente', 'agente_id')
                    ->where(fn ($query) => $query->where('cliente_id', $clienteId)),
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
            'cliente_id' => ['prohibited'],
            'fecha_asignacion' => ['prohibited'],
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'cliente' => ['prohibited'],
            'agente' => ['prohibited'],
            'user' => ['prohibited'],
            'roles' => ['prohibited'],
            'role' => ['prohibited'],
            'permisos' => ['prohibited'],
            'permissions' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'agentes' => ['prohibited'],
            'clientes' => ['prohibited'],
            'inmuebles' => ['prohibited'],
            'intereses' => ['prohibited'],
            'operaciones' => ['prohibited'],
            'citas' => ['prohibited'],
            'oportunidades' => ['prohibited'],
        ];
    }
}
