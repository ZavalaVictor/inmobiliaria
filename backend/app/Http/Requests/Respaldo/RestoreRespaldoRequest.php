<?php

namespace App\Http\Requests\Respaldo;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RestoreRespaldoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirmacion' => ['required', 'string', 'in:RESTAURAR'],
            'database' => ['prohibited'],
            'host' => ['prohibited'],
            'port' => ['prohibited'],
            'username' => ['prohibited'],
            'password' => ['prohibited'],
            'path' => ['prohibited'],
            'filename' => ['prohibited'],
            'command' => ['prohibited'],
            'arguments' => ['prohibited'],
            'user_id' => ['prohibited'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $known = array_keys($this->rules());
        $validator->after(function (Validator $validator) use ($known): void {
            foreach (array_keys($this->all()) as $key) {
                if (! in_array($key, $known, true)) {
                    $validator->errors()->add($key, 'El campo no está permitido.');
                }
            }
        });
    }
}
