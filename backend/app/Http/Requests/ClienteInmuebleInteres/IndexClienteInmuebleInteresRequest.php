<?php

namespace App\Http\Requests\ClienteInmuebleInteres;

use App\Enums\EstadoInteresInmueble;
use App\Enums\NivelInteres;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexClienteInmuebleInteresRequest extends FormRequest
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
            'nivel_interes' => ['sometimes', Rule::enum(NivelInteres::class)],
            'estado' => ['sometimes', Rule::enum(EstadoInteresInmueble::class)],
            'inmueble_id' => [
                'sometimes',
                'integer',
                Rule::exists('inmuebles', 'id')->whereNull('deleted_at'),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'nivel_interes',
                'estado',
                'fecha_interes',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
