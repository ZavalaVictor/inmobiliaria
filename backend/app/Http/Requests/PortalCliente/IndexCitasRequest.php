<?php

namespace App\Http\Requests\PortalCliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexCitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', Rule::in(['proximas', 'historial'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
