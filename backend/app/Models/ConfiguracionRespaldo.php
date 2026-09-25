<?php

namespace App\Models;

use App\Enums\FrecuenciaRespaldo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionRespaldo extends Model
{
    protected $table = 'configuracion_respaldos';

    protected $fillable = [
        'actualizado_por_user_id',
        'activo',
        'frecuencia',
        'hora_ejecucion',
        'dia_semana',
        'dia_mes',
        'retencion_dias',
        'ruta_destino',
        'ultima_ejecucion_at',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'frecuencia' => FrecuenciaRespaldo::class,
            'dia_semana' => 'integer',
            'dia_mes' => 'integer',
            'retencion_dias' => 'integer',
            'ultima_ejecucion_at' => 'datetime',
        ];
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por_user_id');
    }
}
