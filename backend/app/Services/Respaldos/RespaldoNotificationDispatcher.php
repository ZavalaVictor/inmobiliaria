<?php

namespace App\Services\Respaldos;

use App\Enums\EstadoUsuario;
use App\Enums\TipoCorreo;
use App\Models\Respaldo;
use App\Models\User;
use App\Notifications\DatabaseDomainNotification;
use App\Services\Correos\DomainCorreoDispatcher;

final class RespaldoNotificationDispatcher
{
    public function __construct(private readonly DomainCorreoDispatcher $correo) {}

    public function manualCompleted(Respaldo $respaldo): void
    {
        $this->notifyUser($respaldo->generadoPor, $respaldo, 'respaldo_completado', 'Respaldo completado', 'El respaldo manual terminó correctamente.', TipoCorreo::Respaldo);
    }

    public function manualFailed(Respaldo $respaldo): void
    {
        $this->notifyUser($respaldo->generadoPor, $respaldo, 'respaldo_fallido', 'Respaldo fallido', 'El respaldo manual no pudo completarse.', TipoCorreo::Respaldo);
    }

    public function automaticFailed(Respaldo $respaldo): void
    {
        User::query()
            ->where('estado', EstadoUsuario::Activo->value)
            ->whereHas('roles', fn ($query) => $query->where('name', 'Administrador'))
            ->get()
            ->unique('id')
            ->each(fn (User $user) => $this->notifyUser($user, $respaldo, 'respaldo_fallido', 'Respaldo automático fallido', 'Un respaldo automático no pudo completarse.', TipoCorreo::Respaldo));
    }

    public function restoreCompleted(Respaldo $respaldo, User $actor): void
    {
        $this->notifyUser($actor, $respaldo, 'restauracion_completada', 'Restauración completada', 'La restauración del respaldo terminó correctamente.', TipoCorreo::Restauracion, $actor);
    }

    public function restoreFailed(Respaldo $respaldo, User $actor): void
    {
        $this->notifyUser($actor, $respaldo, 'restauracion_fallida', 'Restauración fallida', 'La restauración del respaldo no pudo completarse.', TipoCorreo::Restauracion, $actor);
    }

    private function notifyUser(?User $user, Respaldo $respaldo, string $type, string $title, string $message, TipoCorreo $mailType, ?User $actor = null): void
    {
        if (! $user instanceof User) {
            return;
        }

        $user->notify(new DatabaseDomainNotification([
            'tipo' => $type,
            'titulo' => $title,
            'mensaje' => $message,
            'url' => '/respaldos/'.$respaldo->getKey(),
            'entidad' => ['tipo' => 'respaldo', 'id' => $respaldo->getKey()],
        ]));

        $this->correo->queue(
            $user->email,
            $user,
            $actor,
            null,
            $respaldo,
            $mailType,
            $title,
            trim(implode(' ', array_filter([$user->nombres, $user->apellido_paterno, $user->apellido_materno]))) ?: 'Administrador',
            $message,
            '/respaldos/'.$respaldo->getKey(),
        );
    }
}
