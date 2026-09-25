<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Propietario extends Model
{
    use SoftDeletes;

    protected $table = 'propietarios';

    protected $fillable = [
        'tipo_persona',
        'nombre_razon_social',
        'rfc',
        'telefono',
        'email',
        'direccion',
        'estado_registro',
    ];

    public function inmuebles(): HasMany
    {
        return $this->hasMany(Inmueble::class, 'propietario_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'propietario_id');
    }
}
