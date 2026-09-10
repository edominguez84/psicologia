@extends('layouts.app')

@section('title', 'Política de privacidad · '.config('site.name'))
@section('meta_description', 'Cómo se tratan los datos personales enviados a través de esta web.')

@section('content')
<section class="section bg-white">
    <div class="container-x max-w-3xl prose-soft">
        <p class="eyebrow">Información legal</p>
        <h1 class="mt-3 text-4xl">Política de privacidad</h1>
        <p class="mt-6">
            Esta web tiene carácter informativo sobre los servicios de psicología online de
            {{ config('site.name') }}. A continuación se explica cómo se tratan los datos que
            facilitas a través de los formularios de contacto y del chequeo de bienestar emocional.
        </p>

        <h2 class="mt-10 text-2xl">Responsable del tratamiento</h2>
        <p class="mt-3">
            {{ config('site.name') }} — {{ config('site.role') }} ({{ config('site.registration') }}).
            Puedes contactar en <a class="text-sage-700 underline" href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a>.
        </p>

        <h2 class="mt-10 text-2xl">Qué datos se recogen y con qué finalidad</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5">
            <li><strong>Formulario de contacto:</strong> nombre, email, teléfono (opcional) y el mensaje que escribas. Finalidad: responder a tu consulta y, en su caso, coordinar una primera cita.</li>
            <li><strong>Chequeo de bienestar emocional:</strong> tus respuestas (anónimas) y, opcionalmente, tu email si pides que te escriba. Finalidad: mostrarte un resultado orientativo y, si lo solicitas, ponerme en contacto contigo.</li>
            <li><strong>Datos técnicos:</strong> dirección IP y navegador, con la única finalidad de prevenir el envío masivo de spam.</li>
        </ul>

        <h2 class="mt-10 text-2xl">Base legal</h2>
        <p class="mt-3">
            El tratamiento se basa en tu consentimiento, que otorgas al marcar la casilla
            correspondiente y enviar el formulario, y en el interés legítimo de atender tu solicitud.
        </p>

        <h2 class="mt-10 text-2xl">Conservación y cesiones</h2>
        <p class="mt-3">
            Los datos se conservan el tiempo necesario para atender tu solicitud y, si no llega a
            iniciarse un proceso terapéutico, se eliminan pasado un plazo razonable. <strong>No se
            ceden a terceros</strong> salvo obligación legal. El correo de aviso se gestiona a través
            del proveedor de email configurado por la responsable.
        </p>

        <h2 class="mt-10 text-2xl">Tus derechos</h2>
        <p class="mt-3">
            Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición, limitación y
            portabilidad escribiendo a
            <a class="text-sage-700 underline" href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a>.
        </p>

        <h2 class="mt-10 text-2xl">Aviso importante</h2>
        <p class="mt-3">
            {{ config('site.footer.disclaimer') }}
        </p>

        <p class="mt-10">
            <a href="/" class="btn btn-ghost">← Volver al inicio</a>
        </p>
    </div>
</section>
@endsection
