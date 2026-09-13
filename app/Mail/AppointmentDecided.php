<?php

namespace App\Mail;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica al paciente que su cita fue aprobada o rechazada.
 */
class AppointmentDecided extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        $subject = $this->appointment->status === AppointmentStatus::Approved
            ? 'Tu cita fue confirmada'
            : 'Sobre tu solicitud de cita';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.appointment-decided',
        );
    }
}
