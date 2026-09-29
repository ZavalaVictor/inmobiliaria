<?php

namespace App\Mail\Domain;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class DomainMail extends Mailable
{
    use Queueable;

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (string) $this->data['asunto']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.domains.notification',
            text: 'emails.domains.notification-text',
            with: ['data' => $this->data],
        );
    }
}
