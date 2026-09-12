<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public \DateTimeInterface $expiresAt)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.login-code',
        );
    }
}
