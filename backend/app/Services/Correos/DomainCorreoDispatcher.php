<?php

namespace App\Services\Correos;

use App\Enums\EstadoCorreo;
use App\Enums\TipoCorreo;
use App\Jobs\Correo\SendDomainCorreoJob;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\HistorialCorreo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class DomainCorreoDispatcher
{
    public function queue(
        ?string $email,
        ?User $recipient,
        ?User $actor,
        ?Cliente $cliente,
        ?Model $related,
        TipoCorreo $type,
        string $subject,
        string $recipientName,
        string $message,
        string $url,
    ): ?HistorialCorreo {
        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $email = mb_strtolower(trim($email));
        $data = [
            'tipo' => $type->value,
            'asunto' => $subject,
            'plantilla' => 'emails.domains.notification',
            'destinatario_nombre' => $recipientName,
            'mensaje' => $message,
            'url' => $url,
        ];

        $history = HistorialCorreo::create([
            'destinatario_user_id' => $recipient?->getKey(),
            'cliente_id' => $cliente?->getKey(),
            'cita_id' => $related instanceof Cita ? $related->getKey() : null,
            'relacionado_type' => $related?->getMorphClass(),
            'relacionado_id' => $related?->getKey(),
            'enviado_por_user_id' => $actor?->getKey(),
            'destinatario_email' => $email,
            'destinatario_nombre' => $recipientName,
            'tipo' => $type->value,
            'asunto' => $subject,
            'plantilla' => 'emails.domains.notification',
            'estado' => EstadoCorreo::Pendiente->value,
        ]);

        SendDomainCorreoJob::dispatch($history->getKey(), $data);

        return $history;
    }
}
