<?php

namespace App\Http\Requests\Respaldo;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CreateRespaldoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_fill_keys([
            'database',
            'host',
            'port',
            'username',
            'password',
            'ruta_archivo',
            'ruta_destino',
            'nombre_archivo',
            'tipo',
            'estado',
            'checksum',
            'checksum_sha256',
            'tamano',
            'tamano_bytes',
            'command',
            'arguments',
            'generado_por_user_id',
            'restaurado_por_user_id',
        ], ['prohibited']);
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
