<?php

namespace App\Jobs\Correo;

use App\Enums\EstadoCorreo;
use App\Mail\Domain\DomainMail;
use App\Models\HistorialCorreo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendDomainCorreoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /** @param array<string, mixed> $mailData */
    public function __construct(public readonly int $historialCorreoId, public readonly array $mailData) {}

    public function handle(): void
    {
        $history = HistorialCorreo::query()->find($this->historialCorreoId);
        if ($history === null || ($history->estado?->value ?? $history->estado) !== EstadoCorreo::Pendiente->value) {
            return;
        }

        try {
            Mail::to($history->destinatario_email)->send(new DomainMail($this->mailData));
            $history->update([
                'estado' => EstadoCorreo::Enviado->value,
                'fecha_envio' => now(),
                'mensaje_error' => null,
            ]);
        } catch (Throwable $exception) {
            $history->update([
                'estado' => EstadoCorreo::Fallido->value,
                'fecha_envio' => null,
                'mensaje_error' => $this->safeError($exception),
            ]);
        }
    }

    private function safeError(Throwable $exception): string
    {
        $message = preg_replace('/(password|passwd|secret|token|credential|authorization)\s*[:=]\s*[^\s,;]+/i', '$1=[redacted]', trim($exception->getMessage())) ?? '';
        $message = preg_replace('/[\x00-\x1F\x7F]/', '', $message) ?? '';

        return mb_substr($message === '' ? 'No fue posible enviar el correo.' : 'No fue posible enviar el correo: '.$message, 0, 1000);
    }
}
