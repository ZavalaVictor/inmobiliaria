<?php

namespace App\Models;

use App\Enums\EstadoOportunidad;
use App\Enums\EtapaOportunidad;
use App\Enums\TipoEventoOportunidad;
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
            'tipo_evento' => TipoEventoOportunidad::class,
            'etapa_anterior' => EtapaOportunidad::class,
            'etapa_nueva' => EtapaOportunidad::class,
            'estado_anterior' => EstadoOportunidad::class,
            'estado_nuevo' => EstadoOportunidad::class,
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
