<?php

namespace App\Mail\Citas;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CitaMail extends Mailable
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (string) $this->data['asunto']);
    }

    public function content(): Content
    {
        [$html, $text] = match ($this->data['tipo']) {
            'confirmacion_cita' => ['emails.citas.confirmacion', 'emails.citas.confirmacion-text'],
            'reprogramacion_cita' => ['emails.citas.reprogramacion', 'emails.citas.reprogramacion-text'],
            'cancelacion_cita' => ['emails.citas.cancelacion', 'emails.citas.cancelacion-text'],
        };

        return new Content(
            view: $html,
            text: $text,
            with: ['data' => $this->data],
        );
    }
}
