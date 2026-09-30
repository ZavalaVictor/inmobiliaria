<?php

namespace App\Http\Requests\PortalCliente;

use Illuminate\Foundation\Http\FormRequest;

final class IndexInmueblesInteresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
