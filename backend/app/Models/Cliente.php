<?php

namespace App\Models;

use App\Enums\EstadoCliente;
use App\Enums\TipoInteresCliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'user_id',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'telefono',
        'tipo_interes',
        'presupuesto_min',
        'presupuesto_max',
        'preferencias',
        'estado_cliente',
    ];

    protected function casts(): array
    {
        return [
            'tipo_interes' => TipoInteresCliente::class,
            'estado_cliente' => EstadoCliente::class,
            'presupuesto_min' => 'decimal:2',
            'presupuesto_max' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function interesesInmuebles(): HasMany
    {
        return $this->hasMany(ClienteInmuebleInteres::class, 'cliente_id');
    }

    public function asignacionesAgentes(): HasMany
    {
        return $this->hasMany(ClienteAgente::class, 'cliente_id');
    }

    public function interacciones(): HasMany
    {
        return $this->hasMany(InteraccionCliente::class, 'cliente_id');
    }

    public function solicitudesInformacion(): HasMany
    {
        return $this->hasMany(SolicitudInformacion::class, 'cliente_id');
    }

    public function oportunidades(): HasMany
    {
        return $this->hasMany(Oportunidad::class, 'cliente_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'cliente_id');
    }

    public function operaciones(): HasMany
    {
        return $this->hasMany(Operacion::class, 'cliente_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'cliente_id');
    }

    public function historialCorreos(): HasMany
    {
        return $this->hasMany(HistorialCorreo::class, 'cliente_id');
    }

    public function inmuebles(): BelongsToMany
    {
        return $this->belongsToMany(
            Inmueble::class,
            'cliente_inmueble_intereses',
            'cliente_id',
            'inmueble_id'
        )
            ->withPivot('id', 'nivel_interes', 'estado', 'notas', 'fecha_interes', 'deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function agentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Agente::class,
            'cliente_agente',
            'cliente_id',
            'agente_id'
        )
            ->withPivot('id', 'es_principal', 'fecha_asignacion')
            ->withTimestamps();
    }
}
