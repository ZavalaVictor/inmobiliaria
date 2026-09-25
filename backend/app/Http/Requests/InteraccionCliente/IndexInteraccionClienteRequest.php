<?php

namespace App\Http\Requests\InteraccionCliente;

use App\Enums\TipoInteraccionCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexInteraccionClienteRequest extends FormRequest
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
            'tipo' => ['sometimes', Rule::enum(TipoInteraccionCliente::class)],
            'resultado' => ['sometimes', 'string'],
            'registrado_por_user_id' => ['sometimes', 'integer'],
            'fecha_interaccion_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_interaccion_hasta' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_proxima_accion_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_proxima_accion_hasta' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'q' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'tipo',
                'fecha_interaccion',
                'fecha_proxima_accion',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
