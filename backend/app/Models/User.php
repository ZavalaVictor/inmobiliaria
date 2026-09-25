<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'telefono',
        'password',
        'estado',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoUsuario::class,
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'user_id');
    }

    public function agente(): HasOne
    {
        return $this->hasOne(Agente::class, 'user_id');
    }

    public function interaccionesRegistradas(): HasMany
    {
        return $this->hasMany(InteraccionCliente::class, 'registrado_por_user_id');
    }

    public function solicitudesAtendidas(): HasMany
    {
        return $this->hasMany(SolicitudInformacion::class, 'atendida_por_user_id');
    }

    public function citasCreadas(): HasMany
    {
        return $this->hasMany(Cita::class, 'creado_por_user_id');
    }

    public function cambiosOportunidad(): HasMany
    {
        return $this->hasMany(OportunidadHistorial::class, 'cambiado_por_user_id');
    }

    public function cambiosCita(): HasMany
    {
        return $this->hasMany(CitaHistorial::class, 'modificado_por_user_id');
    }

    public function operacionesRegistradas(): HasMany
    {
        return $this->hasMany(Operacion::class, 'registrado_por_user_id');
    }

    public function documentosSubidos(): HasMany
    {
        return $this->hasMany(Documento::class, 'subido_por_user_id');
    }

    public function correosDestinatario(): HasMany
    {
        return $this->hasMany(HistorialCorreo::class, 'destinatario_user_id');
    }

    public function correosEnviados(): HasMany
    {
        return $this->hasMany(HistorialCorreo::class, 'enviado_por_user_id');
    }

    public function respaldosGenerados(): HasMany
    {
        return $this->hasMany(Respaldo::class, 'generado_por_user_id');
    }

    public function respaldosRestaurados(): HasMany
    {
        return $this->hasMany(Respaldo::class, 'restaurado_por_user_id');
    }

    public function configuracionesActualizadas(): HasMany
    {
        return $this->hasMany(ConfiguracionRespaldo::class, 'actualizado_por_user_id');
    }

    public function bitacora(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'user_id');
    }
}
