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
    $ctaLabel = 'Hablar con '.\App\Support\NameHelper::firstName(config('site.name'));
@endphp

<header class="sticky top-0 z-50 border-b border-paper-200 bg-paper-50/90 backdrop-blur">
    <div class="container-x flex h-16 items-center justify-between gap-4">
        <a href="/" class="flex min-w-0 shrink items-center">
            @include('partials.logo')
        </a>

        <nav class="hidden items-center gap-7 md:flex">
            @foreach ($navLinks as $link)
                <a href="{{ $link['href'] }}" class="text-sm font-semibold text-ink-soft transition-colors hover:text-sky-700">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary hidden sm:inline-flex">
                {{ $ctaLabel }}
            </a>
            @php $mobileNavProps = ['links' => $navLinks, 'whatsapp' => $wa, 'cta' => $ctaLabel]; @endphp
            <div
                data-vue="MobileNav"
                data-props="{{ json_encode($mobileNavProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
            ></div>
        </div>
    </div>
</header>
