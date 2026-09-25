<?php

namespace App\Models;

use App\Enums\EstadoInteresInmueble;
use App\Enums\NivelInteres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteInmuebleInteres extends Model
{
    use SoftDeletes;

    protected $table = 'cliente_inmueble_intereses';

    protected $fillable = [
        'cliente_id',
        'inmueble_id',
        'nivel_interes',
        'estado',
        'notas',
        'fecha_interes',
    ];

    protected function casts(): array
    {
        return [
            'nivel_interes' => NivelInteres::class,
            'estado' => EstadoInteresInmueble::class,
            'fecha_interes' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }
}
