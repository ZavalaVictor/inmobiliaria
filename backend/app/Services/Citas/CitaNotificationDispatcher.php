<?php

namespace App\Services\Citas;

use App\Enums\EstadoCorreo;
use App\Enums\TipoCorreo;
use App\Jobs\Citas\SendCitaCorreoJob;
use App\Models\Cita;
use App\Models\CitaHistorial;
use App\Models\HistorialCorreo;
use App\Models\User;
use App\Notifications\Citas\CitaCanceladaNotification;
use App\Notifications\Citas\CitaConfirmadaNotification;
use App\Notifications\Citas\CitaReprogramadaNotification;
use Illuminate\Support\Str;

final class CitaNotificationDispatcher
{
    public function created(Cita $cita, User $actor): void
    {
        $this->dispatch($cita, $actor, TipoCorreo::ConfirmacionCita, new CitaConfirmadaNotification($cita));
    }

    public function rescheduled(Cita $cita, CitaHistorial $history, User $actor): void
    {
        $this->dispatch($cita, $actor, TipoCorreo::ReprogramacionCita, new CitaReprogramadaNotification($cita, $history), $history);
    }

    public function cancelled(Cita $cita, User $actor): void
    {
        $this->dispatch($cita, $actor, TipoCorreo::CancelacionCita, new CitaCanceladaNotification($cita));
    }

    private function dispatch(
        Cita $cita,
        User $actor,
        TipoCorreo $type,
        object $notification,
        ?CitaHistorial $history = null,
    ): void {
        $cita = $cita->fresh(['cliente.user', 'agente.user', 'inmueble']);
        $recipients = $this->recipients($cita);
        $internalRecipients = [];
        $emailRecipients = [];

        foreach ($recipients as $recipient) {
            if ($recipient['user'] instanceof User) {
                $internalRecipients[$recipient['user']->getKey()] = $recipient;
            }

            if ($recipient['email'] !== null) {
                $emailRecipients[Str::lower($recipient['email'])] = $recipient;
            }
        }

        foreach ($internalRecipients as $recipient) {
            $recipient['user']->notify($notification);
        }

        foreach ($emailRecipients as $recipient) {
            $mailData = $this->mailData($cita, $type, $history, $recipient['name']);
            $historyRecord = HistorialCorreo::create([
                'destinatario_user_id' => $recipient['user']?->getKey(),
                'cliente_id' => $cita->cliente_id,
                'cita_id' => $cita->getKey(),
                'relacionado_type' => $cita->getMorphClass(),
                'relacionado_id' => $cita->getKey(),
                'enviado_por_user_id' => $actor->getKey(),
                'destinatario_email' => $recipient['email'],
                'destinatario_nombre' => $recipient['name'],
                'tipo' => $type->value,
                'asunto' => $mailData['asunto'],
                'plantilla' => $mailData['plantilla'],
                'estado' => EstadoCorreo::Pendiente->value,
            ]);

            SendCitaCorreoJob::dispatch($historyRecord->getKey(), $mailData);
        }
    }

    /**
     * @return array<int, array{user: ?User, email: ?string, name: string}>
     */
    private function recipients(Cita $cita): array
    {
        $recipients = [];
        $client = $cita->cliente;
        $clientUser = $client?->user;
        $clientEmail = $this->validEmail($client?->email) ?? $this->validEmail($clientUser?->email);

        if ($client !== null) {
            $recipients[] = [
                'user' => $clientUser,
                'email' => $clientEmail,
                'name' => $this->fullName($client->nombres, $client->apellido_paterno, $client->apellido_materno),
            ];
        }

        $agentUser = $cita->agente?->user;

        if ($agentUser !== null) {
            $recipients[] = [
                'user' => $agentUser,
                'email' => $this->validEmail($agentUser->email),
                'name' => $this->fullName($agentUser->nombres, $agentUser->apellido_paterno, $agentUser->apellido_materno),
            ];
        }

        return $recipients;
    }

    /**
     * @return array<string, mixed>
     */
    private function mailData(Cita $cita, TipoCorreo $type, ?CitaHistorial $history, string $recipientName): array
    {
        $data = [
            'tipo' => $type->value,
            'asunto' => match ($type) {
                TipoCorreo::ConfirmacionCita => 'Confirmación de cita',
                TipoCorreo::ReprogramacionCita => 'Reprogramación de cita',
                TipoCorreo::CancelacionCita => 'Cancelación de cita',
                default => 'Actualización de cita',
            },
            'plantilla' => match ($type) {
                TipoCorreo::ConfirmacionCita => 'emails.citas.confirmacion',
                TipoCorreo::ReprogramacionCita => 'emails.citas.reprogramacion',
                TipoCorreo::CancelacionCita => 'emails.citas.cancelacion',
                default => 'emails.citas.confirmacion',
            },
            'destinatario_nombre' => $recipientName,
            'cita_id' => $cita->getKey(),
            'inmueble_titulo' => $cita->inmueble?->titulo,
            'fecha_inicio' => $cita->fecha_inicio->format('Y-m-d H:i'),
            'fecha_fin' => $cita->fecha_fin->format('Y-m-d H:i'),
            'url' => '/citas/'.$cita->getKey(),
        ];

        if ($history !== null) {
            $data['fecha_inicio_anterior'] = $history->fecha_inicio_anterior->format('Y-m-d H:i');
            $data['fecha_fin_anterior'] = $history->fecha_fin_anterior->format('Y-m-d H:i');
            $data['fecha_inicio_nueva'] = $history->fecha_inicio_nueva->format('Y-m-d H:i');
            $data['fecha_fin_nueva'] = $history->fecha_fin_nueva->format('Y-m-d H:i');
        }

        return $data;
    }

    private function validEmail(?string $email): ?string
    {
        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            ? $email
            : null;
    }

    private function fullName(?string $first, ?string $last, ?string $maternal): string
    {
        return trim(implode(' ', array_filter([$first, $last, $maternal])));
    }
}
