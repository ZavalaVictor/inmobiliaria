<?php

namespace App\Http\Requests\Documento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class CreateDocumentoRequest extends FormRequest
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
            'archivo' => [
                'required',
                File::types(['pdf', 'png', 'jpg', 'jpeg'])->max((int) config('services.firebase.document_max_kb', 10240)),
            ],
            'categoria_documento_id' => [
                'required',
                'integer',
                Rule::exists('categorias_documentos', 'id')
                    ->whereNull('deleted_at')
                    ->where('activo', true),
            ],
            'propietario_id' => ['sometimes', 'nullable', 'integer', Rule::exists('propietarios', 'id')->whereNull('deleted_at')],
            'cliente_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'inmueble_id' => ['sometimes', 'nullable', 'integer', Rule::exists('inmuebles', 'id')->whereNull('deleted_at')],
            'operacion_id' => ['sometimes', 'nullable', 'integer', Rule::exists('operaciones', 'id')],
            'fecha_documento' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'fecha_vencimiento' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:500'],
            ...$this->prohibitedRules(),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach (array_keys($this->prohibitedRules()) as $field) {
                if (array_key_exists($field, $this->all())) {
                    $validator->errors()->add($field, 'Este campo no está permitido.');
                }
            }

            $destinations = collect(['propietario_id', 'cliente_id', 'inmueble_id', 'operacion_id'])
                ->filter(fn (string $key): bool => $this->input($key) !== null)
                ->values();

            if ($destinations->count() !== 1) {
                $validator->errors()->add('destino', 'Debe especificarse exactamente un destino.');
            }

            $from = $this->input('fecha_documento');
            $to = $this->input('fecha_vencimiento');

            if ($from !== null && $to !== null && $to < $from) {
                $validator->errors()->add('fecha_vencimiento', 'La fecha de vencimiento debe ser posterior o igual a la fecha del documento.');
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
            'subido_por_user_id' => ['prohibited'],
            'nombre_original' => ['prohibited'],
            'firebase_path' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'tamano_bytes' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'categoriaDocumento' => ['prohibited'],
            'subidoPor' => ['prohibited'],
            'propietario' => ['prohibited'],
            'cliente' => ['prohibited'],
            'inmueble' => ['prohibited'],
            'operacion' => ['prohibited'],
            'documentos' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
