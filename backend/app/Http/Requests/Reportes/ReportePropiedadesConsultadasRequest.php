<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportePropiedadesConsultadasRequest extends ReportDateRangeRequest
{
    public function rules(): array
    {
        return $this->addRangeRules([
            'categoria_id' => ['sometimes', 'integer', Rule::exists('categorias', 'id')->whereNull('deleted_at')],
            'zona' => ['sometimes', 'string', 'max:100'],
            'precio_min' => ['sometimes', 'numeric', 'min:0'],
            'precio_max' => ['sometimes', 'numeric', 'min:0', Rule::when($this->filled('precio_min'), ['gte:precio_min'])],
        ]);
    }

    protected function withValidator(Validator $validator): void
    {
        $this->withRangeValidation($validator);
    }
}
