@php
    $wa = 'https://wa.me/'.config('site.contact.whatsapp').'?text='.rawurlencode(config('site.whatsapp_prefill'));

    $footerSectionLinks = [
        ['href' => '/#sobre-mi', 'label' => 'Sobre mí', 'section' => 'about'],
        ['href' => '/#servicios', 'label' => 'Cómo te ayudo', 'section' => 'services'],
        ['href' => '/#emdr', 'label' => 'Terapia EMDR', 'section' => 'emdr'],
        ['href' => '/#chequeo', 'label' => 'Chequeo emocional', 'section' => 'checkup'],
        ['href' => '/#faq', 'label' => 'Preguntas frecuentes', 'section' => 'faq'],
    ];
    $footerSectionVisibility = app(\App\Services\SiteSettingsService::class)->get('section_visibility', []);
    $footerSectionLinks = array_values(array_filter(
        $footerSectionLinks,
        fn ($link) => $footerSectionVisibility[$link['section']] ?? true
    ));
@endphp

<footer class="border-t border-paper-200 bg-ink-panel text-on-dark-soft">
    <div class="container-x grid gap-10 py-14 md:grid-cols-[1.4fr_1fr_1fr]">
        <div>
            @include('partials.logo', ['dark' => true])
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-on-dark-soft">
                {{ config('site.role') }}. {{ config('site.registration') }}.
            </p>
            <p class="mt-4 text-xs leading-relaxed text-on-dark-soft/80">
                {{ config('site.footer.disclaimer') }}
            </p>
        </div>

        <div>
            <h4 class="text-sm font-bold uppercase tracking-wider text-on-dark">Secciones</h4>
            <ul class="mt-4 space-y-2 text-sm text-on-dark-soft">
                @foreach ($footerSectionLinks as $link)
                    <li><a href="{{ $link['href'] }}" class="hover:text-on-dark">{{ $link['label'] }}</a></li>
                @endforeach
                <li><a href="/privacidad" class="hover:text-on-dark">Política de privacidad</a></li>
                <li><a href="/condiciones-de-uso" class="hover:text-on-dark">Condiciones de uso</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-sm font-bold uppercase tracking-wider text-on-dark">Contacto</h4>
            <ul class="mt-4 space-y-2 text-sm text-on-dark-soft">
                <li><a href="{{ $wa }}" target="_blank" rel="noopener" class="hover:text-on-dark">WhatsApp {{ config('site.contact.whatsapp_show') }}</a></li>
                <li><a href="mailto:{{ config('site.contact.email') }}" class="hover:text-on-dark">{{ config('site.contact.email') }}</a></li>
                <li>{{ config('site.contact.area') }}</li>
                <li>{{ config('site.contact.response') }}</li>
            </ul>

            <div class="mt-6">
                @include('partials.social-icons', ['dark' => true, 'heading' => 'Redes sociales'])
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-x flex flex-col gap-3 py-6 text-xs leading-relaxed text-on-dark-soft/70 md:flex-row md:flex-wrap md:items-center md:justify-between md:gap-x-6 md:gap-y-2">
            <p class="shrink-0">© {{ date('Y') }} {{ config('site.name') }}. Todos los derechos reservados.</p>
            <p class="md:flex-1 md:text-center">{{ config('site.footer.privacy_note') }}</p>
            <a href="{{ route('login') }}" class="shrink-0 text-on-dark-soft/50 underline-offset-2 hover:text-on-dark hover:underline">
                Acceso administración
            </a>
        </div>
    </div>
</footer>
