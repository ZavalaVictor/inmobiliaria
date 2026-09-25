<?php

namespace App\Models;

use App\Enums\EstadoRespaldo;
use App\Enums\EstadoRestauracionRespaldo;
use App\Enums\TipoRespaldo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Respaldo extends Model
{
    protected $table = 'respaldos';

    protected $fillable = [
        'generado_por_user_id',
        'restaurado_por_user_id',
        'tipo',
        'nombre_archivo',
        'ruta_archivo',
        'tamano_bytes',
        'checksum_sha256',
        'estado',
        'fecha_inicio',
        'fecha_finalizacion',
        'restaurado_at',
        'estado_restauracion',
        'mensaje_error',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoRespaldo::class,
            'estado' => EstadoRespaldo::class,
            'estado_restauracion' => EstadoRestauracionRespaldo::class,
            'fecha_inicio' => 'datetime',
            'fecha_finalizacion' => 'datetime',
            'restaurado_at' => 'datetime',
        ];
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por_user_id');
    }

    public function restauradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restaurado_por_user_id');
    }
}
