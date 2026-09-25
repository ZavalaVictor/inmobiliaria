<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InteraccionCliente extends Model
{
    use SoftDeletes;

    protected $table = 'interacciones_cliente';

    protected $fillable = [
        'cliente_id',
        'registrado_por_user_id',
        'tipo',
        'descripcion',
        'resultado',
        'fecha_interaccion',
        'proxima_accion',
        'fecha_proxima_accion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_interaccion' => 'datetime',
            'fecha_proxima_accion' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
