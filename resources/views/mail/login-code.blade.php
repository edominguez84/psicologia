@component('mail::message')
# Tu código de acceso

Usa este código para completar tu inicio de sesión:

<div style="text-align:center; font-size:32px; font-weight:700; letter-spacing:0.5em; font-family:monospace; margin:24px 0;">
{{ $code }}
</div>

Caduca el {{ $expiresAt->format('d/m/Y H:i') }} (en 2 horas). Si expira o no te llegó, pulsa
"Reenviar código" en la pantalla de verificación para pedir uno nuevo.

Si no solicitaste este código, ignora este correo — tu cuenta sigue segura.
@endcomponent
