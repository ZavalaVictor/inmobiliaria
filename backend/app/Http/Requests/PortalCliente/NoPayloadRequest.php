<?php

namespace App\Http\Requests\PortalCliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class NoPayloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->all() !== []) {
                $validator->errors()->add('payload', 'Este endpoint no acepta datos en el cuerpo.');
            }
        });
    }
}
