<?php

namespace App\Http\Requests\Respaldo;

use App\Enums\FrecuenciaRespaldo;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfiguracionRespaldoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activo' => ['sometimes', 'boolean'],
            'frecuencia' => ['sometimes', Rule::enum(FrecuenciaRespaldo::class)],
            'hora_ejecucion' => ['sometimes', 'date_format:H:i:s'],
            'dia_semana' => ['sometimes', 'nullable', 'integer', 'between:1,7'],
            'dia_mes' => ['sometimes', 'nullable', 'integer', 'between:1,28'],
            'retencion_dias' => ['sometimes', 'integer', 'min:1'],
            'id' => ['prohibited'],
            'actualizado_por_user_id' => ['prohibited'],
            'ruta_destino' => ['prohibited'],
            'ultima_ejecucion_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'database' => ['prohibited'],
            'host' => ['prohibited'],
            'port' => ['prohibited'],
            'username' => ['prohibited'],
            'password' => ['prohibited'],
            'credentials' => ['prohibited'],
            'command' => ['prohibited'],
            'path' => ['prohibited'],
            'filename' => ['prohibited'],
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
