<?php

namespace App\Notifications\Solicitudes;

use App\Models\SolicitudInformacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class SolicitudAsignadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly SolicitudInformacion $solicitud) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'tipo' => 'solicitud_asignada',
            'titulo' => 'Solicitud asignada',
            'mensaje' => 'Se te asignó una solicitud de información.',
            'url' => '/solicitudes/'.$this->solicitud->getKey(),
            'entidad' => [
                'tipo' => 'solicitud',
                'id' => $this->solicitud->getKey(),
            ],
        ]);
    }
}
