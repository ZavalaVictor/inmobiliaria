<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documento extends Model
{
    use SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'categoria_documento_id',
        'subido_por_user_id',
        'propietario_id',
        'cliente_id',
        'inmueble_id',
        'operacion_id',
        'nombre_original',
        'firebase_path',
        'mime_type',
        'tamano_bytes',
        'fecha_documento',
        'fecha_vencimiento',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_documento' => 'date',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function categoriaDocumento(): BelongsTo
    {
        return $this->belongsTo(CategoriaDocumento::class, 'categoria_documento_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por_user_id');
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class, 'operacion_id');
    }
}
