<?php

namespace App\Http\Requests\Reportes;

use App\Services\Reportes\ReportDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReporteComparativoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_a_desde' => ['required', 'date_format:Y-m-d'],
            'periodo_a_hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodo_a_desde'],
            'periodo_b_desde' => ['required', 'date_format:Y-m-d'],
            'periodo_b_hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodo_b_desde'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ([['periodo_a_desde', 'periodo_a_hasta'], ['periodo_b_desde', 'periodo_b_hasta']] as [$from, $to]) {
                if (! $this->filled($from) || ! $this->filled($to)) {
                    continue;
                }

                try {
                    $range = ReportDateRange::fromStrings((string) $this->input($from), (string) $this->input($to));
                } catch (\Throwable) {
                    continue;
                }

                if ($range->inicio->startOfDay()->addYear()->lt($range->fin->startOfDay())) {
                    $validator->errors()->add($to, 'El rango máximo permitido es de un año.');
                }
            }
        });
    }

    /** @return array{a: ReportDateRange, b: ReportDateRange} */
    public function ranges(): array
    {
        return [
            'a' => ReportDateRange::fromStrings($this->validated('periodo_a_desde'), $this->validated('periodo_a_hasta')),
            'b' => ReportDateRange::fromStrings($this->validated('periodo_b_desde'), $this->validated('periodo_b_hasta')),
        ];
    }
}
