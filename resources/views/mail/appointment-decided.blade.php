@component('mail::message')
@if ($appointment->status->value === 'approved')
# Tu cita fue confirmada

Tu cita para el **{{ $appointment->appointmentSlot->starts_at->format('d/m/Y H:i') }}** ha sido
confirmada. Te esperamos.

@if ($appointment->admin_note)
---

{{ $appointment->admin_note }}
@endif
@else
# Sobre tu solicitud de cita

Lamentablemente no podemos confirmar tu cita para el
**{{ $appointment->appointmentSlot->starts_at->format('d/m/Y H:i') }}**.

@if ($appointment->admin_note)
---

{{ $appointment->admin_note }}
@endif

Puedes solicitar otro horario disponible desde tu cuenta cuando quieras.
@endif

@component('mail::button', ['url' => route('patient.appointments.index')])
Ver mis citas
@endcomponent
@endcomponent
