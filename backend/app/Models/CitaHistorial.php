<?php

namespace App\Models;

use App\Enums\TipoCambioCita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitaHistorial extends Model
{
    protected $table = 'cita_historial';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'cita_id',
        'modificado_por_user_id',
        'agente_anterior_id',
        'agente_nuevo_id',
        'fecha_inicio_anterior',
        'fecha_fin_anterior',
        'fecha_inicio_nueva',
        'fecha_fin_nueva',
        'motivo',
        'tipo_cambio',
        'fecha_modificacion',
    ];

    protected function casts(): array
    {
        return [
            'tipo_cambio' => TipoCambioCita::class,
            'fecha_inicio_anterior' => 'datetime',
            'fecha_fin_anterior' => 'datetime',
            'fecha_inicio_nueva' => 'datetime',
            'fecha_fin_nueva' => 'datetime',
            'fecha_modificacion' => 'datetime',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function modificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modificado_por_user_id');
    }

    public function agenteAnterior(): BelongsTo
    {
        return $this->belongsTo(Agente::class, 'agente_anterior_id');
    }

    public function agenteNuevo(): BelongsTo
    {
        return $this->belongsTo(Agente::class, 'agente_nuevo_id');
    }
}
