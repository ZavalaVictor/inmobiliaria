<?php

namespace App\Http\Requests\Reportes;

use App\Services\Reportes\ReportDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class ReportDateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function addRangeRules(array $rules): array
    {
        return [
            ...$rules,
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }

    protected function withRangeValidation(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('desde') || ! $this->filled('hasta')) {
                return;
            }

            try {
                $range = ReportDateRange::fromStrings((string) $this->input('desde'), (string) $this->input('hasta'));
            } catch (\Throwable) {
                return;
            }

            if ($range->inicio->startOfDay()->addYear()->lt($range->fin->startOfDay())) {
                $validator->errors()->add('hasta', 'El rango máximo permitido es de un año.');
            }
        });
    }

    public function range(): ReportDateRange
    {
        return ReportDateRange::fromStrings($this->validated('desde'), $this->validated('hasta'));
    }
}
