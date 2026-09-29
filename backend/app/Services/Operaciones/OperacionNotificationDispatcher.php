<?php

namespace App\Services\Operaciones;

use App\Enums\TipoCorreo;
use App\Models\Agente;
use App\Models\Cliente;
use App\Models\Operacion;
use App\Models\User;
use App\Notifications\DatabaseDomainNotification;
use App\Services\Correos\DomainCorreoDispatcher;

final class OperacionNotificationDispatcher
{
    public function __construct(private readonly DomainCorreoDispatcher $correo) {}

    public function assigned(Operacion $operacion, Agente $agent, ?User $actor): void
    {
        $user = $agent->user;
        if ($user instanceof User && ! $actor?->is($user)) {
            $this->notifyAgent($operacion, $user, $actor, 'operacion_creada', 'Agente relacionado con operación', 'Fuiste relacionado con una operación.', TipoCorreo::OperacionCreada);
        }
    }

    public function principalChanged(Operacion $operacion, Agente $agent, ?User $actor): void
    {
        $user = $agent->user;
        if ($user instanceof User && ! $actor?->is($user)) {
            $this->notifyAgent($operacion, $user, $actor, 'operacion_creada', 'Agente principal de operación', 'Fuiste establecido como agente principal de una operación.', TipoCorreo::OperacionCreada);
        }
    }

    public function terminal(Operacion $operacion, ?User $actor): void
    {
        $operacion = $operacion->fresh(['asignacionesAgentes.agente.user', 'cliente.user']);
        $seenUsers = [];
        $seenEmails = [];
        foreach ($operacion->asignacionesAgentes as $assignment) {
            $user = $assignment->agente?->user;
            $email = $user?->email;
            if (! $user instanceof User || $actor?->is($user) || isset($seenUsers[$user->id]) || ($email !== null && isset($seenEmails[mb_strtolower($email)]))) {
                continue;
            }
            $seenUsers[$user->id] = true;
            if ($email !== null) {
                $seenEmails[mb_strtolower($email)] = true;
            }
            $this->notifyAgent($operacion, $user, $actor, 'operacion_cierre', 'Actualización de operación', 'La operación alcanzó un estado final.', TipoCorreo::OperacionCierre);
        }

        $client = $operacion->cliente;
        $user = $client?->user;
        $email = $user?->email ?? $client?->email;
        if ($client instanceof Cliente
            && (! $user instanceof User || ! $actor?->is($user))
            && (! $user instanceof User || ! isset($seenUsers[$user->id]))
            && ($email === null || ! isset($seenEmails[mb_strtolower($email)]))) {
            $this->notifyClient($operacion, $client, $user, $actor);
        }
    }

    private function notifyAgent(Operacion $operacion, User $user, ?User $actor, string $type, string $title, string $message, TipoCorreo $mailType): void
    {
        $this->send($operacion, $user, null, $actor, $type, $title, $message, $mailType, $user->email);
    }

    private function notifyClient(Operacion $operacion, Cliente $client, ?User $user, ?User $actor): void
    {
        $this->send($operacion, $user, $client, $actor, 'operacion_cierre', 'Resultado de operación', 'Tu operación tiene una actualización final.', TipoCorreo::OperacionCierre, $user?->email ?? $client->email);
    }

    private function send(Operacion $operacion, ?User $user, ?Cliente $client, ?User $actor, string $type, string $title, string $message, TipoCorreo $mailType, ?string $email): void
    {
        if ($user instanceof User) {
            $user->notify(new DatabaseDomainNotification([
                'tipo' => $type,
                'titulo' => $title,
                'mensaje' => $message,
                'url' => '/operaciones/'.$operacion->getKey(),
                'entidad' => ['tipo' => 'operacion', 'id' => $operacion->getKey()],
            ]));
        }

        $this->correo->queue(
            $email,
            $user,
            $actor,
            $client,
            $operacion,
            $mailType,
            $title,
            $this->name($user, $client),
            $message,
            '/operaciones/'.$operacion->getKey(),
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
}
