@php
    $wa = 'https://wa.me/'.config('site.contact.whatsapp').'?text='.rawurlencode(config('site.whatsapp_prefill'));
@endphp

<footer class="border-t border-paper-200 bg-sky-800 text-paper-100">
    <div class="container-x grid gap-10 py-14 md:grid-cols-[1.4fr_1fr_1fr]">
        <div>
            @include('partials.logo', ['dark' => true])
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-paper-200">
                {{ config('site.role') }}. {{ config('site.registration') }}.
            </p>
            <p class="mt-4 text-xs leading-relaxed text-paper-200/80">
                {{ config('site.footer.disclaimer') }}
            </p>
        </div>

        <div>
            <h4 class="text-sm font-bold uppercase tracking-wider text-paper-50">Secciones</h4>
            <ul class="mt-4 space-y-2 text-sm text-paper-200">
                <li><a href="/#sobre-mi" class="hover:text-paper-50">Sobre mí</a></li>
                <li><a href="/#servicios" class="hover:text-paper-50">Cómo te ayudo</a></li>
                <li><a href="/#emdr" class="hover:text-paper-50">Terapia EMDR</a></li>
                <li><a href="/#chequeo" class="hover:text-paper-50">Chequeo emocional</a></li>
                <li><a href="/#faq" class="hover:text-paper-50">Preguntas frecuentes</a></li>
                <li><a href="/privacidad" class="hover:text-paper-50">Política de privacidad</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-sm font-bold uppercase tracking-wider text-paper-50">Contacto</h4>
            <ul class="mt-4 space-y-2 text-sm text-paper-200">
                <li><a href="{{ $wa }}" target="_blank" rel="noopener" class="hover:text-paper-50">WhatsApp {{ config('site.contact.whatsapp_show') }}</a></li>
                <li><a href="mailto:{{ config('site.contact.email') }}" class="hover:text-paper-50">{{ config('site.contact.email') }}</a></li>
                <li>{{ config('site.contact.area') }}</li>
                <li>{{ config('site.contact.response') }}</li>
            </ul>

            <div class="mt-6">
                @include('partials.social-icons', ['dark' => true, 'heading' => 'Redes sociales'])
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-x flex flex-col gap-3 py-6 text-xs leading-relaxed text-paper-200/70 md:flex-row md:flex-wrap md:items-center md:justify-between md:gap-x-6 md:gap-y-2">
            <p class="shrink-0">© {{ date('Y') }} {{ config('site.name') }}. Todos los derechos reservados.</p>
            <p class="md:flex-1 md:text-center">{{ config('site.footer.privacy_note') }}</p>
            <a href="{{ route('login') }}" class="shrink-0 text-paper-200/50 underline-offset-2 hover:text-paper-50 hover:underline">
                Acceso administración
            </a>
        </div>
    </div>
</footer>
