<?php

namespace App\Http\Requests\Inmueble;

use App\Enums\EstadoDisponibilidadInmueble;
use App\Enums\TipoOperacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexInmuebleRequest extends FormRequest
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
            'q' => ['sometimes', 'string', 'max:255'],
            'tipo_operacion' => ['sometimes', Rule::enum(TipoOperacion::class)],
            'estado_disponibilidad' => ['sometimes', Rule::enum(EstadoDisponibilidadInmueble::class)],
            'categoria_id' => [
                'sometimes',
                'integer',
                Rule::exists('categorias', 'id')->whereNull('deleted_at'),
            ],
            'propietario_id' => [
                'sometimes',
                'integer',
                Rule::exists('propietarios', 'id')->whereNull('deleted_at'),
            ],
            'habitaciones' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'banos_completos' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'publicado' => ['sometimes', 'boolean'],
            'municipio' => ['sometimes', 'string', 'max:100'],
            'estado_ubicacion' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'codigo',
                'titulo',
                'tipo_operacion',
                'estado_disponibilidad',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
