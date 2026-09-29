<?php

namespace App\Notifications\Citas;

use App\Models\Cita;
use App\Models\CitaHistorial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CitaReprogramadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Cita $cita,
        private readonly CitaHistorial $history,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage(CitaNotificationPayload::rescheduled($this->cita, $this->history));
    }
}
