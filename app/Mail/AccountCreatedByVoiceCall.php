<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Se envía cuando el asistente de VAPI crea una cuenta nueva durante el
 * registro de paciente por llamada de voz (ver VapiToolCallController,
 * VapiCallService::callForVoiceRegistration). A diferencia de
 * AccountCreatedByChatbot, esta llamada NO agenda ninguna cita — solo crea
 * la cuenta, así que no recibe ningún Appointment. Incluye la contraseña
 * temporal generada, igual criterio que el resto de flujos de "contraseña
 * temporal por email" del sitio.
 */
class AccountCreatedByVoiceCall extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu cuenta en '.config('site.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-created-by-voice-call',
        );
    }
}
