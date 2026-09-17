@component('mail::message')
# ¡Hola, {{ $user->name }}!

Nuestro asistente virtual creó una cuenta para ti en **{{ config('site.name') }}** y registró tu
solicitud de cita para el **{{ $appointment->appointmentSlot->starts_at->format('d/m/Y H:i') }}**.

Tu solicitud queda pendiente de confirmación — te avisaremos por este mismo correo en cuanto se
confirme.

---

**Estos son los datos de tu cuenta**, por si quieres entrar a ver o gestionar tu cita más adelante:

- **Correo:** {{ $user->email }}
- **Contraseña temporal:** `{{ $temporaryPassword }}`

Te recomendamos cambiar esta contraseña la primera vez que inicies sesión.

@component('mail::button', ['url' => route('login')])
Iniciar sesión
@endcomponent
@endcomponent
