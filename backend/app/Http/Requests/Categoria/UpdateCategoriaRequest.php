<?php

namespace App\Http\Requests\Categoria;

use App\Models\Categoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends FormRequest
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
        $uniqueName = Rule::unique('categorias', 'nombre');

        if ($categoria instanceof Categoria) {
            $uniqueName->ignore($categoria->getKey());
        }

        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:80', $uniqueName],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
            ...$this->prohibitedRules(),
        ];
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
            'inmuebles' => ['prohibited'],
            'relaciones' => ['prohibited'],
            'roles' => ['prohibited'],
            'permisos' => ['prohibited'],
        ];
    }
}
