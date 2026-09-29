<?php

namespace App\Services\Solicitudes;

use App\Enums\EstadoUsuario;
use App\Enums\TipoCorreo;
use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Notifications\DatabaseDomainNotification;
use App\Notifications\Solicitudes\SolicitudAsignadaNotification;
use App\Services\Correos\DomainCorreoDispatcher;

final class SolicitudNotificationDispatcher
{
    public function __construct(private readonly DomainCorreoDispatcher $correo) {}

    public function created(SolicitudInformacion $solicitud, ?User $actor = null): void
    {
        $users = User::query()
            ->where('estado', EstadoUsuario::Activo->value)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Administrador', 'Asistente']))
            ->get();

        $sentEmails = [];
        foreach ($users->unique('id') as $user) {
            if ($actor?->is($user)) {
                continue;
            }

            $user->notify(new DatabaseDomainNotification($this->payload(
                'solicitud_nueva',
                'Nueva solicitud de información',
                'Se recibió una nueva solicitud de información.',
                $solicitud,
            )));

            $email = is_string($user->email) ? mb_strtolower(trim($user->email)) : null;
            if ($email !== null && ! isset($sentEmails[$email])) {
                $sentEmails[$email] = true;
                $this->correo->queue(
                    $user->email,
                    $user,
                    $actor,
                    $solicitud->cliente,
                    $solicitud,
                    TipoCorreo::SolicitudRecibida,
                    'Nueva solicitud de información',
                    $this->fullName($user),
                    'Se recibió una nueva solicitud de información.',
                    '/solicitudes/'.$solicitud->getKey(),
                );
            }
        }
    }

    public function publicCreated(SolicitudInformacion $solicitud): void
    {
        $this->correo->queue(
            $solicitud->email,
            null,
            null,
            null,
            $solicitud,
            TipoCorreo::SolicitudRecibida,
            'Solicitud recibida',
            $solicitud->nombre,
            'Recibimos tu solicitud de información. Nuestro equipo dará seguimiento.',
            '/solicitudes/'.$solicitud->getKey(),
        );
    }

    public function assigned(SolicitudInformacion $solicitud, ?User $actor = null): void
    {
        $responsable = $solicitud->atendidaPor;

        if ($responsable !== null) {
            $responsable->notify(new SolicitudAsignadaNotification($solicitud));
            $this->correo->queue(
                $responsable->email,
                $responsable,
                $actor,
                $solicitud->cliente,
                $solicitud,
                TipoCorreo::SolicitudAsignada,
                'Solicitud asignada',
                $this->fullName($responsable),
                'Se te asignó una solicitud de información.',
                '/solicitudes/'.$solicitud->getKey(),
            );
        }
    }

    /** @return array<string, mixed> */
    private function payload(string $type, string $title, string $message, SolicitudInformacion $solicitud): array
    {
        return [
            'tipo' => $type,
            'titulo' => $title,
            'mensaje' => $message,
            'url' => '/solicitudes/'.$solicitud->getKey(),
            'entidad' => ['tipo' => 'solicitud', 'id' => $solicitud->getKey()],
        ];
    }

    private function fullName(User $user): string
    {
        return trim(implode(' ', array_filter([$user->nombres, $user->apellido_paterno, $user->apellido_materno]))) ?: 'Usuario';
    }
}
