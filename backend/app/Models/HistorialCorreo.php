<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialCorreo extends Model
{
    protected $table = 'historial_correos';

    protected $fillable = [
        'destinatario_user_id',
        'cliente_id',
        'cita_id',
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

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por_user_id');
    }
}
