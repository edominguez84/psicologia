@php
    $wa = 'https://wa.me/'.config('site.contact.whatsapp').'?text='.rawurlencode(config('site.whatsapp_prefill'));
@endphp

<footer class="border-t border-cream-200 bg-sage-800 text-cream-100">
    <div class="container-x grid gap-10 py-14 md:grid-cols-[1.4fr_1fr_1fr]">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="grid size-9 place-items-center rounded-full bg-cream-50 font-serif text-lg text-sage-800">
                    {{ mb_substr(config('site.name'), 0, 1) }}
                </span>
                <span class="font-serif text-lg text-cream-50">{{ config('site.name') }}</span>
            </div>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-cream-200">
                {{ config('site.role') }}. {{ config('site.registration') }}.
            </p>
            <p class="mt-4 text-xs leading-relaxed text-cream-200/80">
                {{ config('site.footer.disclaimer') }}
            </p>
        </div>

        <div>
            <h4 class="text-sm font-bold uppercase tracking-wider text-cream-50">Secciones</h4>
            <ul class="mt-4 space-y-2 text-sm text-cream-200">
                <li><a href="/#sobre-mi" class="hover:text-cream-50">Sobre mí</a></li>
                <li><a href="/#servicios" class="hover:text-cream-50">Cómo te ayudo</a></li>
                <li><a href="/#emdr" class="hover:text-cream-50">Terapia EMDR</a></li>
                <li><a href="/#chequeo" class="hover:text-cream-50">Chequeo emocional</a></li>
                <li><a href="/#faq" class="hover:text-cream-50">Preguntas frecuentes</a></li>
                <li><a href="/privacidad" class="hover:text-cream-50">Política de privacidad</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-sm font-bold uppercase tracking-wider text-cream-50">Contacto</h4>
            <ul class="mt-4 space-y-2 text-sm text-cream-200">
                <li><a href="{{ $wa }}" target="_blank" rel="noopener" class="hover:text-cream-50">WhatsApp {{ config('site.contact.whatsapp_show') }}</a></li>
                <li><a href="mailto:{{ config('site.contact.email') }}" class="hover:text-cream-50">{{ config('site.contact.email') }}</a></li>
                <li>{{ config('site.contact.area') }}</li>
                <li>{{ config('site.contact.response') }}</li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-x flex flex-col gap-2 py-6 text-xs text-cream-200/70 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} {{ config('site.name') }}. Todos los derechos reservados.</p>
            <p>{{ config('site.footer.privacy_note') }}</p>
        </div>
    </div>
</footer>
