<?php

namespace App\Http\Requests\Documento;

use App\Models\Documento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentoRequest extends FormRequest
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
            'categoria_documento_id' => [
                'sometimes',
                'integer',
                Rule::exists('categorias_documentos', 'id')
                    ->whereNull('deleted_at')
                    ->where('activo', true),
            ],
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

            $documento = $this->route('documento');

            if (! $documento instanceof Documento) {
                return;
            }

            $fechaDocumento = array_key_exists('fecha_documento', $this->all())
                ? $this->input('fecha_documento')
                : $documento->fecha_documento?->format('Y-m-d');
            $fechaVencimiento = array_key_exists('fecha_vencimiento', $this->all())
                ? $this->input('fecha_vencimiento')
                : $documento->fecha_vencimiento?->format('Y-m-d');

            if ($fechaDocumento !== null && $fechaVencimiento !== null && $fechaVencimiento < $fechaDocumento) {
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
            'propietario_id' => ['prohibited'],
            'cliente_id' => ['prohibited'],
            'inmueble_id' => ['prohibited'],
            'operacion_id' => ['prohibited'],
            'subido_por_user_id' => ['prohibited'],
            'nombre_original' => ['prohibited'],
            'firebase_path' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'tamano_bytes' => ['prohibited'],
            'archivo' => ['prohibited'],
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
