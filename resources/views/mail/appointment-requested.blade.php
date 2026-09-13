@component('mail::message')
# Nueva solicitud de cita

**Paciente:** {{ $appointment->user->name }}
**Email:** {{ $appointment->user->email }}
**Teléfono:** {{ $appointment->user->phone_number ?: '—' }}
**Horario solicitado:** {{ $appointment->appointmentSlot->starts_at->format('d/m/Y H:i') }} a {{ $appointment->appointmentSlot->ends_at->format('H:i') }}

@if ($appointment->patient_note)
---

{{ $appointment->patient_note }}
@endif

---

Solicitada el {{ $appointment->created_at->format('d/m/Y H:i') }}

@component('mail::button', ['url' => route('admin.appointments.index')])
Ver solicitud en el panel
@endcomponent
@endcomponent
