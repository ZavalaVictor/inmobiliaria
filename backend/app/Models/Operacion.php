<?php

namespace App\Models;

use App\Enums\EstadoOperacion;
use App\Enums\TipoOperacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operacion extends Model
{
    protected $table = 'operaciones';

    protected $fillable = [
        'oportunidad_id',
        'cliente_id',
        'inmueble_id',
        'registrado_por_user_id',
        'tipo_operacion',
        'monto',
        'fecha_operacion',
        'fecha_inicio_contrato',
        'fecha_fin_contrato',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'tipo_operacion' => TipoOperacion::class,
            'estado' => EstadoOperacion::class,
            'monto' => 'decimal:2',
            'fecha_operacion' => 'datetime',
            'fecha_inicio_contrato' => 'date',
            'fecha_fin_contrato' => 'date',
        ];
    }

    public function oportunidad(): BelongsTo
    {
        return $this->belongsTo(Oportunidad::class, 'oportunidad_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public function asignacionesAgentes(): HasMany
    {
        return $this->hasMany(OperacionAgente::class, 'operacion_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'operacion_id');
    }

    public function agentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Agente::class,
            'operacion_agentes',
            'operacion_id',
            'agente_id'
        )
            ->withPivot('id', 'es_principal', 'porcentaje_comision', 'monto_comision')
            ->withTimestamps();
    }
}
