<?php

namespace App\Http\Requests\HistorialCorreo;

use App\Enums\EstadoCorreo;
use App\Enums\TipoCorreo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexHistorialCorreoRequest extends FormRequest
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
            'estado' => ['sometimes', Rule::enum(EstadoCorreo::class)],
            'tipo' => ['sometimes', Rule::enum(TipoCorreo::class)],
            'destinatario_user_id' => ['sometimes', 'integer', 'min:1'],
            'cliente_id' => ['sometimes', 'integer', 'min:1'],
            'cita_id' => ['sometimes', 'integer', 'min:1'],
            'enviado_por_user_id' => ['sometimes', 'integer', 'min:1'],
            'destinatario_email' => ['sometimes', 'string', 'max:150'],
            'fecha_envio_desde' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'fecha_envio_hasta' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'q' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in([
                'id',
                'destinatario_email',
                'tipo',
                'estado',
                'asunto',
                'fecha_envio',
                'created_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
