<?php

namespace App\Models;

use App\Enums\EstadoSolicitudInformacion;
use App\Enums\MedioSolicitudInformacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudInformacion extends Model
{
    use SoftDeletes;

    protected $table = 'solicitudes_informacion';

    protected $fillable = [
        'inmueble_id',
        'cliente_id',
        'atendida_por_user_id',
        'nombre',
        'email',
        'telefono',
        'mensaje',
        'medio_preferido',
        'estado',
        'origen',
        'fecha_solicitud',
        'fecha_atencion',
    ];

    protected function casts(): array
    {
        return [
            'medio_preferido' => MedioSolicitudInformacion::class,
            'estado' => EstadoSolicitudInformacion::class,
            'fecha_solicitud' => 'datetime',
            'fecha_atencion' => 'datetime',
        ];
    }

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function atendidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendida_por_user_id');
    }

    public function oportunidad(): HasOne
    {
        return $this->hasOne(Oportunidad::class, 'solicitud_informacion_id');
    }
}
