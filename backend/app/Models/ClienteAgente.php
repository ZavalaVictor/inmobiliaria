<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteAgente extends Model
{
    protected $table = 'cliente_agente';

    protected $fillable = [
        'cliente_id',
        'agente_id',
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

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(Agente::class, 'agente_id');
    }
}
