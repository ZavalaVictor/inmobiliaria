<?php

namespace App\Http\Requests\Documento;

use App\Enums\MimeTypeDocumento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDocumentoRequest extends FormRequest
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
                Rule::exists('categorias_documentos', 'id')->whereNull('deleted_at'),
            ],
            'subido_por_user_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'mime_type' => ['sometimes', Rule::enum(MimeTypeDocumento::class)],
            'propietario_id' => ['sometimes', 'integer', Rule::exists('propietarios', 'id')->whereNull('deleted_at')],
            'cliente_id' => ['sometimes', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'inmueble_id' => ['sometimes', 'integer', Rule::exists('inmuebles', 'id')->whereNull('deleted_at')],
            'operacion_id' => ['sometimes', 'integer', Rule::exists('operaciones', 'id')],
            'destino' => ['sometimes', Rule::in(['propietario', 'cliente', 'inmueble', 'operacion'])],
            'fecha_documento_desde' => ['sometimes', 'date_format:Y-m-d'],
            'fecha_documento_hasta' => ['sometimes', 'date_format:Y-m-d'],
            'fecha_vencimiento_desde' => ['sometimes', 'date_format:Y-m-d'],
            'fecha_vencimiento_hasta' => ['sometimes', 'date_format:Y-m-d'],
            'q' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'nombre_original',
                'mime_type',
                'fecha_documento',
                'fecha_vencimiento',
                'created_at',
                'updated_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->validateDateRange($validator, 'fecha_documento_desde', 'fecha_documento_hasta');
            $this->validateDateRange($validator, 'fecha_vencimiento_desde', 'fecha_vencimiento_hasta');
        });
    }

    private function validateDateRange($validator, string $from, string $to): void
    {
        if ($this->input($from) !== null && $this->input($to) !== null && $this->input($to) < $this->input($from)) {
            $validator->errors()->add($to, 'El rango de fechas no es válido.');
        }
    }
}
