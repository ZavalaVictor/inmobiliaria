<?php

namespace App\Models;

use App\Enums\EstadoOportunidad;
use App\Enums\EtapaOportunidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Oportunidad extends Model
{
    use SoftDeletes;

    protected $table = 'oportunidades';

    protected $fillable = [
        'cliente_id',
        'inmueble_id',
        'agente_principal_id',
        'solicitud_informacion_id',
        'titulo',
        'etapa',
        'estado',
        'notas',
        'fecha_apertura',
        'fecha_cierre',
        'motivo_perdida',
    ];

    protected function casts(): array
    {
        return [
            'etapa' => EtapaOportunidad::class,
            'estado' => EstadoOportunidad::class,
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
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

    public function agentePrincipal(): BelongsTo
    {
        return $this->belongsTo(Agente::class, 'agente_principal_id');
    }

    public function solicitudInformacion(): BelongsTo
    {
        return $this->belongsTo(SolicitudInformacion::class, 'solicitud_informacion_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(OportunidadHistorial::class, 'oportunidad_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'oportunidad_id');
    }

    public function operacion(): HasOne
    {
        return $this->hasOne(Operacion::class, 'oportunidad_id');
    }
}
