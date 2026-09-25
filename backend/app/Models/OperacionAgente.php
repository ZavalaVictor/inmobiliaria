<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperacionAgente extends Model
{
    protected $table = 'operacion_agentes';

    protected $fillable = [
        'operacion_id',
        'agente_id',
        'es_principal',
        'porcentaje_comision',
        'monto_comision',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'porcentaje_comision' => 'decimal:2',
            'monto_comision' => 'decimal:2',
        ];
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class, 'operacion_id');
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(Agente::class, 'agente_id');
    }
}
