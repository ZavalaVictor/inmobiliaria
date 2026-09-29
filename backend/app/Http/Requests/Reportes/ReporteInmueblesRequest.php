<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReporteInmueblesRequest extends ReportDateRangeRequest
{
    public function rules(): array
    {
        return $this->addRangeRules([
            'zona' => ['sometimes', 'string', 'max:100'],
            'categoria_id' => ['sometimes', 'integer', Rule::exists('categorias', 'id')->whereNull('deleted_at')],
            'estado' => ['sometimes', Rule::in(['disponible', 'vendido', 'rentado', 'inactivo'])],
            'agente_id' => ['sometimes', 'integer', Rule::exists('agentes', 'id')->whereNull('deleted_at')],
        ]);
    }

    protected function withValidator(Validator $validator): void
    {
        $this->withRangeValidation($validator);
    }
}
