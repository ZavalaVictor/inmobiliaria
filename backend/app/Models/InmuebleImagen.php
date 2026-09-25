<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InmuebleImagen extends Model
{
    use SoftDeletes;

    protected $table = 'inmueble_imagenes';

    protected $fillable = [
        'inmueble_id',
        'firebase_path',
        'url_publica',
        'nombre_original',
        'mime_type',
        'tamano_bytes',
        'es_principal',
        'orden',
        'texto_alternativo',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }
}
