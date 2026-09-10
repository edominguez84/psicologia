@component('mail::message')
# Nuevo mensaje de contacto

**Nombre:** {{ $contactMessage->name }}
**Email:** {{ $contactMessage->email }}
**Teléfono:** {{ $contactMessage->phone ?: '—' }}
**Asunto:** {{ $contactMessage->subject ?: '—' }}
**Vía preferida:** {{ $contactMessage->preferred_contact ?: '—' }}

---

{{ $contactMessage->message }}

---

Recibido el {{ $contactMessage->created_at->format('d/m/Y H:i') }} · IP {{ $contactMessage->ip }}

@component('mail::button', ['url' => 'mailto:'.$contactMessage->email])
Responder
@endcomponent
@endcomponent
