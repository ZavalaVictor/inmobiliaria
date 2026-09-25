<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OportunidadHistorial extends Model
{
    protected $table = 'oportunidad_historial';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'oportunidad_id',
        'cambiado_por_user_id',
        'tipo_evento',
        'etapa_anterior',
        'etapa_nueva',
        'estado_anterior',
        'estado_nuevo',
        'comentario',
        'fecha_cambio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cambio' => 'datetime',
        ];
    }

    public function oportunidad(): BelongsTo
    {
        return $this->belongsTo(Oportunidad::class, 'oportunidad_id');
    }

    public function cambiadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cambiado_por_user_id');
    }
}
