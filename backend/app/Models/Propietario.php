<?php

namespace App\Models;

use App\Enums\EstadoRegistroPropietario;
use App\Enums\TipoPersonaPropietario;
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

    protected function casts(): array
    {
        return [
            'tipo_persona' => TipoPersonaPropietario::class,
            'estado_registro' => EstadoRegistroPropietario::class,
        ];
    }

    public function inmuebles(): HasMany
    {
        return $this->hasMany(Inmueble::class, 'propietario_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'propietario_id');
    }
}
