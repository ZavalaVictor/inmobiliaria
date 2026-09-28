<?php

namespace App\Http\Requests\CategoriaDocumento;

use App\Models\CategoriaDocumento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaDocumentoRequest extends FormRequest
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
        $categoria = $this->route('categoria');
        $uniqueName = Rule::unique('categorias_documentos', 'nombre');

        if ($categoria instanceof CategoriaDocumento) {
            $uniqueName->ignore($categoria->getKey());
        }

        return [
            'nombre' => ['sometimes', 'string', 'max:100', $uniqueName],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
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
        });
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'documentos' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'permisos' => ['prohibited'],
            'relaciones' => ['prohibited'],
        ];
    }
}
