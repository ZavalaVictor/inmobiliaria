<?php

namespace App\Services\Documentos;

use App\Enums\TipoCorreo;
use App\Models\Documento;
use App\Models\User;
use App\Notifications\DatabaseDomainNotification;
use App\Services\Correos\DomainCorreoDispatcher;

final class DocumentoNotificationDispatcher
{
    public function __construct(private readonly DomainCorreoDispatcher $correo) {}

    public function created(Documento $documento, ?User $actor): void
    {
        $documento = $documento->fresh([
            'inmueble.asignacionesAgentes.agente.user',
            'cliente.asignacionesAgentes.agente.user',
            'operacion.asignacionesAgentes.agente.user',
        ]);

        $agents = collect();
        if ($documento->inmueble !== null) {
            $agents = $agents->merge($documento->inmueble->asignacionesAgentes->pluck('agente'));
        }
        if ($documento->cliente !== null) {
            $agents = $agents->merge($documento->cliente->asignacionesAgentes->pluck('agente'));
        }
        if ($documento->operacion !== null) {
            $agents = $agents->merge($documento->operacion->asignacionesAgentes->pluck('agente'));
        }

        $seenUsers = [];
        $seenEmails = [];
        foreach ($agents->filter()->unique('id') as $agent) {
            $user = $agent->user;
            $email = $user?->email;
            if (! $user instanceof User
                || $actor?->is($user)
                || isset($seenUsers[$user->id])
                || ($email !== null && isset($seenEmails[mb_strtolower($email)]))) {
                continue;
            }
            $seenUsers[$user->id] = true;
            if ($email !== null) {
                $seenEmails[mb_strtolower($email)] = true;
            }

            $user->notify(new DatabaseDomainNotification([
                'tipo' => 'documento_cargado',
                'titulo' => 'Nuevo documento cargado',
                'mensaje' => 'Se cargó un documento relacionado con una entidad de tu alcance.',
                'url' => '/documentos/'.$documento->getKey(),
                'entidad' => ['tipo' => 'documento', 'id' => $documento->getKey()],
            ]));

            $this->correo->queue(
                $user->email,
                $user,
                $actor,
                $documento->cliente,
                $documento,
                TipoCorreo::DocumentoCargado,
                'Nuevo documento cargado',
                trim(implode(' ', array_filter([$user->nombres, $user->apellido_paterno, $user->apellido_materno]))) ?: 'Usuario',
                'Se cargó un documento relacionado con una entidad de tu alcance.',
                '/documentos/'.$documento->getKey(),
            );
        }
    }
}
