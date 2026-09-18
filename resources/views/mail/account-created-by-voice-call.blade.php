@component('mail::message')
# ¡Hola, {{ $user->name }}!

Acabamos de crear tu cuenta en **{{ config('site.name') }}** durante la llamada telefónica que
tuviste con nuestro asistente. Ya puedes entrar al sitio para elegir el horario de tu cita y el
método de pago que prefieras.

---

**Estos son los datos de tu cuenta:**

- **Correo:** {{ $user->email }}
- **Contraseña temporal:** `{{ $temporaryPassword }}`

Te recomendamos cambiar esta contraseña la primera vez que inicies sesión.

@component('mail::button', ['url' => route('login')])
Iniciar sesión
@endcomponent
@endcomponent
