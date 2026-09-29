<?php

namespace App\Services\Oportunidades;

use App\Enums\TipoCorreo;
use App\Models\Cliente;
use App\Models\Oportunidad;
use App\Models\User;
use App\Notifications\DatabaseDomainNotification;
use App\Services\Correos\DomainCorreoDispatcher;

final class OportunidadNotificationDispatcher
{
    public function __construct(private readonly DomainCorreoDispatcher $correo) {}

    public function stageChanged(Oportunidad $oportunidad, ?User $actor, string $stage): void
    {
        $oportunidad = $this->withRecipients($oportunidad);
        $this->dispatchAgent($oportunidad, $actor, 'oportunidad_cambio_etapa', 'Cambio de etapa de oportunidad', 'La oportunidad cambió de etapa.', TipoCorreo::OportunidadCambioEtapa);

        if ($stage === 'cierre') {
            $this->dispatchClient($oportunidad, $actor, 'oportunidad_cierre', 'Resultado de oportunidad', 'La oportunidad alcanzó un resultado de cierre.', TipoCorreo::OportunidadCierre);
        }
    }

    public function stateChanged(Oportunidad $oportunidad, ?User $actor, string $state): void
    {
        if (! in_array($state, ['ganada', 'perdida', 'cancelada'], true)) {
            return;
        }

        $oportunidad = $this->withRecipients($oportunidad);
        $this->dispatchAgent($oportunidad, $actor, 'oportunidad_cierre', 'Resultado de oportunidad', 'La oportunidad alcanzó un resultado de cierre.', TipoCorreo::OportunidadCierre);
        $agentUser = $oportunidad->agentePrincipal?->user;
        $clientUser = $oportunidad->cliente?->user;
        $agentEmail = $agentUser?->email;
        $clientEmail = $clientUser?->email ?? $oportunidad->cliente?->email;
        if (($agentUser instanceof User && $clientUser instanceof User && $agentUser->is($clientUser))
            || ($agentEmail !== null && $clientEmail !== null && mb_strtolower($agentEmail) === mb_strtolower($clientEmail))) {
            return;
        }

        $this->dispatchClient($oportunidad, $actor, 'oportunidad_cierre', 'Resultado de oportunidad', 'Tu solicitud de atención inmobiliaria tiene un resultado actualizado.', TipoCorreo::OportunidadCierre);
    }

    private function dispatchAgent(Oportunidad $oportunidad, ?User $actor, string $type, string $title, string $message, TipoCorreo $mailType): void
    {
        $user = $oportunidad->agentePrincipal?->user;
        if (! $user instanceof User || $actor?->is($user)) {
            return;
        }

        $this->notify($user, $actor, $oportunidad, $type, $title, $message, $mailType);
    }

    private function dispatchClient(Oportunidad $oportunidad, ?User $actor, string $type, string $title, string $message, TipoCorreo $mailType): void
    {
        $client = $oportunidad->cliente;
        if (! $client instanceof Cliente) {
            return;
        }

        $user = $client->user;
        if ($user instanceof User && $actor?->is($user)) {
            return;
        }

        $this->notify($user, $actor, $oportunidad, $type, $title, $message, $mailType, $client, $user?->email ?? $client->email);
    }

    private function notify(?User $user, ?User $actor, Oportunidad $oportunidad, string $type, string $title, string $message, TipoCorreo $mailType, ?Cliente $client = null, ?string $email = null): void
    {
        if ($user instanceof User) {
            $user->notify(new DatabaseDomainNotification([
                'tipo' => $type,
                'titulo' => $title,
                'mensaje' => $message,
                'url' => '/oportunidades/'.$oportunidad->getKey(),
                'entidad' => ['tipo' => 'oportunidad', 'id' => $oportunidad->getKey()],
            ]));
        }

        $this->correo->queue(
            $email ?? $user?->email,
            $user,
            $actor,
            $client,
            $oportunidad,
            $mailType,
            $title,
            $this->name($user, $client),
            $message,
            '/oportunidades/'.$oportunidad->getKey(),
        );
    }

    private function name(?User $user, ?Cliente $client): string
    {
        return trim(implode(' ', array_filter([
            $user?->nombres ?? $client?->nombres,
            $user?->apellido_paterno ?? $client?->apellido_paterno,
            $user?->apellido_materno ?? $client?->apellido_materno,
        ]))) ?: 'Usuario';
    }

    private function withRecipients(Oportunidad $oportunidad): Oportunidad
    {
        return $oportunidad->fresh([
            'cliente:id,nombres,apellido_paterno,apellido_materno,email',
            'cliente.user:id,nombres,apellido_paterno,apellido_materno,email',
            'agentePrincipal:id,numero_empleado,user_id',
            'agentePrincipal.user:id,nombres,apellido_paterno,apellido_materno,email',
        ]);
    }
}
