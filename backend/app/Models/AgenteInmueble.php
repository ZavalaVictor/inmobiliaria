<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgenteInmueble extends Model
{
    protected $table = 'agente_inmueble';

    protected $fillable = [
        'agente_id',
        'inmueble_id',
        'es_principal',
        'fecha_asignacion',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'fecha_asignacion' => 'datetime',
        ];
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(Agente::class, 'agente_id');
    }

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }
}
