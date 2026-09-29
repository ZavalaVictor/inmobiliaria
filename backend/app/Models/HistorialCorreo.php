<?php

namespace App\Models;

use App\Enums\EstadoCorreo;
use App\Enums\TipoCorreo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HistorialCorreo extends Model
{
    protected $table = 'historial_correos';

    protected $fillable = [
        'destinatario_user_id',
        'cliente_id',
        'cita_id',
        'relacionado_type',
        'relacionado_id',
        'enviado_por_user_id',
        'destinatario_email',
        'destinatario_nombre',
        'tipo',
        'asunto',
        'plantilla',
        'estado',
        'proveedor_message_id',
        'fecha_envio',
        'mensaje_error',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoCorreo::class,
            'estado' => EstadoCorreo::class,
            'fecha_envio' => 'datetime',
        ];
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_user_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function relacionado(): MorphTo
    {
        return $this->morphTo();
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por_user_id');
    }
}
