<?php

namespace App\Http\Requests\Reportes;

use App\Enums\EstadoCita;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReporteCitasRequest extends ReportDateRangeRequest
{
    public function rules(): array
    {
        return $this->addRangeRules([
            'agente_id' => ['sometimes', 'integer', Rule::exists('agentes', 'id')->whereNull('deleted_at')],
            'inmueble_id' => ['sometimes', 'integer', Rule::exists('inmuebles', 'id')->whereNull('deleted_at')],
            'estado' => ['sometimes', Rule::enum(EstadoCita::class)],
        ]);
    }

    protected function withValidator(Validator $validator): void
    {
        $this->withRangeValidation($validator);
    }
}
