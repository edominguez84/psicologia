<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Se envía cuando el chatbot con IA crea una cuenta nueva para agendar una
 * cita en la misma conversación (ver ChatbotAiService::toolBookAppointment).
 * Incluye la contraseña temporal generada — la única vez que viaja en
 * texto plano, igual que cualquier flujo de "contraseña temporal por
 * email" — para que la persona pueda entrar a su cuenta después y gestionar
 * su cita normalmente.
 */
class AccountCreatedByChatbot extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
        public Appointment $appointment,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu cuenta y tu solicitud de cita — '.config('site.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-created-by-chatbot',
        );
    }
}
