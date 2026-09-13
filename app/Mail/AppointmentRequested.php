<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica a la(s) super administradora(s) que un paciente solicitó una
 * cita nueva.
 */
class AppointmentRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de cita — '.$this->appointment->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.appointment-requested',
        );
    }
}
