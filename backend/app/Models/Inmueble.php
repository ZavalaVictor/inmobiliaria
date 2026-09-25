<?php

namespace App\Models;

use App\Enums\EstadoDisponibilidadInmueble;
use App\Enums\TipoOperacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inmueble extends Model
{
    use SoftDeletes;

    protected $table = 'inmuebles';

    protected $fillable = [
        'propietario_id',
        'categoria_id',
        'codigo',
        'titulo',
        'slug',
        'descripcion',
        'tipo_operacion',
        'precio_venta',
        'renta_mensual',
        'superficie_terreno_m2',
        'superficie_construccion_m2',
        'habitaciones',
        'banos_completos',
        'medios_banos',
        'estacionamientos',
        'niveles',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'municipio',
        'estado_ubicacion',
        'codigo_postal',
        'referencias',
        'latitud',
        'longitud',
        'estado_disponibilidad',
        'publicado',
        'fecha_publicacion',
    ];

    protected function casts(): array
    {
        return [
            'tipo_operacion' => TipoOperacion::class,
            'estado_disponibilidad' => EstadoDisponibilidadInmueble::class,
            'precio_venta' => 'decimal:2',
            'renta_mensual' => 'decimal:2',
            'superficie_terreno_m2' => 'decimal:2',
            'superficie_construccion_m2' => 'decimal:2',
            'habitaciones' => 'integer',
            'banos_completos' => 'integer',
            'medios_banos' => 'integer',
            'estacionamientos' => 'integer',
            'niveles' => 'integer',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'publicado' => 'boolean',
            'fecha_publicacion' => 'datetime',
        ];
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(InmuebleImagen::class, 'inmueble_id');
    }

    protected function childRouteBindingRelationshipName($childType): string
    {
        return match ($childType) {
            'imagen' => 'imagenes',
            'asignacion' => 'asignacionesAgentes',
            default => parent::childRouteBindingRelationshipName($childType),
        };
    }

    protected function resolveChildRouteBindingQuery($childType, $value, $field)
    {
        $query = parent::resolveChildRouteBindingQuery($childType, $value, $field);

        return $childType === 'asignacion'
            ? $query->whereHas('agente')
            : $query;
    }

    public function interesesClientes(): HasMany
    {
        return $this->hasMany(ClienteInmuebleInteres::class, 'inmueble_id');
    }

    public function asignacionesAgentes(): HasMany
    {
        return $this->hasMany(AgenteInmueble::class, 'inmueble_id');
    }

    public function solicitudesInformacion(): HasMany
    {
        return $this->hasMany(SolicitudInformacion::class, 'inmueble_id');
    }

    public function oportunidades(): HasMany
    {
        return $this->hasMany(Oportunidad::class, 'inmueble_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'inmueble_id');
    }

    public function operaciones(): HasMany
    {
        return $this->hasMany(Operacion::class, 'inmueble_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'inmueble_id');
    }

    public function visualizaciones(): HasMany
    {
        return $this->hasMany(VisualizacionInmueble::class, 'inmueble_id');
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Cliente::class,
            'cliente_inmueble_intereses',
            'inmueble_id',
            'cliente_id'
        )
            ->withPivot('id', 'nivel_interes', 'estado', 'notas', 'fecha_interes', 'deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function agentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Agente::class,
            'agente_inmueble',
            'inmueble_id',
            'agente_id'
        )
            ->withPivot('id', 'es_principal', 'fecha_asignacion')
            ->withTimestamps();
    }
}
