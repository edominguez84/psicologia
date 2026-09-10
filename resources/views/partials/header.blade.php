@php
    $navLinks = [
        ['href' => '/#sobre-mi', 'label' => 'Sobre mí'],
        ['href' => '/#servicios', 'label' => 'Cómo te ayudo'],
        ['href' => '/#beneficios', 'label' => 'Beneficios'],
        ['href' => '/#emdr', 'label' => 'Terapia EMDR'],
        ['href' => '/#chequeo', 'label' => 'Chequeo emocional'],
        ['href' => '/#faq', 'label' => 'Preguntas'],
    ];
    $wa = 'https://wa.me/'.config('site.contact.whatsapp').'?text='.rawurlencode(config('site.whatsapp_prefill'));
@endphp

<header class="sticky top-0 z-50 border-b border-cream-200 bg-cream-50/90 backdrop-blur">
    <div class="container-x flex h-16 items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-full bg-sage-600 font-serif text-lg text-cream-50">
                {{ mb_substr(config('site.name'), 0, 1) }}
            </span>
            <span class="font-serif text-lg leading-none text-sage-800">{{ config('site.name') }}</span>
        </a>

        <nav class="hidden items-center gap-7 md:flex">
            @foreach ($navLinks as $link)
                <a href="{{ $link['href'] }}" class="text-sm font-semibold text-ink-soft transition-colors hover:text-sage-700">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary hidden sm:inline-flex">
                Escríbeme
            </a>
            @php $mobileNavProps = ['links' => $navLinks, 'whatsapp' => $wa, 'cta' => 'Escríbeme por WhatsApp']; @endphp
            <div
                data-vue="MobileNav"
                data-props="{{ json_encode($mobileNavProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
            ></div>
        </div>
    </div>
</header>
