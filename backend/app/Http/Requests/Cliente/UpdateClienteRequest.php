<?php

namespace App\Http\Requests\Cliente;

use App\Enums\EstadoCliente;
use App\Enums\TipoInteresCliente;
use App\Models\Cliente;
use App\Support\Authorization\ActorScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClienteRequest extends FormRequest
{
    private const MAX_PRESUPUESTO = '999999999999.99';

    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $this->user() !== null
            && $cliente instanceof Cliente
            && $this->user()->can('update', $cliente);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'nombres' => ['sometimes', 'string', 'max:100'],
            'apellido_paterno' => ['sometimes', 'string', 'max:80'],
            'apellido_materno' => ['sometimes', 'nullable', 'string', 'max:80'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'tipo_interes' => ['sometimes', 'nullable', Rule::enum(TipoInteresCliente::class)],
            'presupuesto_min' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_PRESUPUESTO],
            'presupuesto_max' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_PRESUPUESTO],
            'preferencias' => ['sometimes', 'nullable', 'string'],
            ...$this->prohibitedRules(),
        ];

        if (! ActorScope::isClient($this->user())) {
            $rules['email'] = ['sometimes', 'nullable', 'email', 'max:150'];
            $rules['estado_cliente'] = ['sometimes', Rule::enum(EstadoCliente::class)];
        } else {
            $rules['email'] = ['prohibited'];
            $rules['estado_cliente'] = ['prohibited'];
        }

        return $rules;
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cliente = $this->route('cliente');

            if (! $cliente instanceof Cliente) {
                return;
            }

            $minimum = $this->input('presupuesto_min', $cliente->presupuesto_min);
            $maximum = $this->input('presupuesto_max', $cliente->presupuesto_max);

            if ($minimum !== null && $maximum !== null && (float) $maximum < (float) $minimum) {
                $validator->errors()->add(
                    'presupuesto_max',
                    'El presupuesto máximo debe ser mayor o igual al presupuesto mínimo.'
                );
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
            'user_id' => ['prohibited'],
            'agente_id' => ['prohibited'],
            'agentes' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'roles' => ['prohibited'],
            'permisos' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
