<?php

namespace App\Models;

use App\Enums\EstadoLaboralAgente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agente extends Model
{
    use SoftDeletes;

    protected $table = 'agentes';

    protected $fillable = [
        'user_id',
        'numero_empleado',
        'telefono_corporativo',
        'zona_asignacion',
        'horario',
        'porcentaje_comision',
        'foto_path',
        'estado_laboral',
        'fecha_contratacion',
    ];

    protected function casts(): array
    {
        return [
            'estado_laboral' => EstadoLaboralAgente::class,
            'porcentaje_comision' => 'decimal:2',
            'fecha_contratacion' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asignacionesInmuebles(): HasMany
    {
        return $this->hasMany(AgenteInmueble::class, 'agente_id');
    }

    public function asignacionesClientes(): HasMany
    {
        return $this->hasMany(ClienteAgente::class, 'agente_id');
    }

    public function oportunidadesPrincipales(): HasMany
    {
        return $this->hasMany(Oportunidad::class, 'agente_principal_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'agente_id');
    }

    public function historialComoAgenteAnterior(): HasMany
    {
        return $this->hasMany(CitaHistorial::class, 'agente_anterior_id');
    }

    public function historialComoAgenteNuevo(): HasMany
    {
        return $this->hasMany(CitaHistorial::class, 'agente_nuevo_id');
    }

    public function asignacionesOperaciones(): HasMany
    {
        return $this->hasMany(OperacionAgente::class, 'agente_id');
    }

    public function inmuebles(): BelongsToMany
    {
        return $this->belongsToMany(
            Inmueble::class,
            'agente_inmueble',
            'agente_id',
            'inmueble_id'
        )
            ->withPivot('id', 'es_principal', 'fecha_asignacion')
            ->withTimestamps();
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Cliente::class,
            'cliente_agente',
            'agente_id',
            'cliente_id'
        )
            ->withPivot('id', 'es_principal', 'fecha_asignacion')
            ->withTimestamps();
    }

    public function operaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Operacion::class,
            'operacion_agentes',
            'agente_id',
            'operacion_id'
        )
            ->withPivot('id', 'es_principal', 'porcentaje_comision', 'monto_comision')
            ->withTimestamps();
    }
}
