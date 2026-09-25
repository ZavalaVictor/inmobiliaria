<?php

namespace App\Models;

use App\Enums\OrigenVisualizacionInmueble;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisualizacionInmueble extends Model
{
    protected $table = 'visualizaciones_inmuebles';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'inmueble_id',
        'session_id',
        'ip_hash',
        'user_agent',
        'referer',
        'origen',
        'fecha_visualizacion',
    ];

    protected function casts(): array
    {
        return [
            'origen' => OrigenVisualizacionInmueble::class,
            'fecha_visualizacion' => 'datetime',
        ];
    }

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }
}
