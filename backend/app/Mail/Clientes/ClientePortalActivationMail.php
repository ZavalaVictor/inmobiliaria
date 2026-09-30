<?php

namespace App\Mail\Clientes;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class ClientePortalActivationMail extends Mailable
{
    use Queueable;

    public function __construct(
        private readonly string $recipientName,
        private readonly string $url,
        private readonly int $expires,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Configura tu acceso al portal');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.clientes.portal-activacion',
            text: 'emails.clientes.portal-activacion-text',
            with: [
                'recipientName' => $this->recipientName,
                'url' => $this->url,
                'expires' => $this->expires,
            ],
        );
    }
}
