@extends('layouts.app')

@php
    $s = config('site');
    $wa = 'https://wa.me/'.$s['contact']['whatsapp'].'?text='.rawurlencode($s['whatsapp_prefill']);

    // Modo demo: para exportar una versión estática (p. ej. Netlify) sin backend.
    // Los formularios calculan/arman el mensaje en el navegador en vez de llamar a la API.
    $demoMode = (bool) config('site.demo_mode');

    // Props para los componentes Vue (se serializan a JSON en el atributo data-props).
    $mythProps = ['items' => $s['myths']['items']];
    $faqProps = ['items' => $s['faq']['items']];
    $checkupProps = [
        'questions'  => $s['checkup']['questions'],
        'options'    => $s['checkup']['options'],
        'period'     => $s['checkup']['period'],
        'disclaimer' => $s['checkup']['disclaimer'],
        'endpoint'   => $demoMode ? null : route('checkup.store'),
        'whatsapp'   => $wa,
        'demoMode'   => $demoMode,
        'results'    => $s['checkup']['results'],
    ];
    // Campos del formulario de contacto: cuáles fijos mostrar y cuáles
    // personalizados añadió el admin desde /admin/contact-form.
    $contactFormSettings = app(\App\Services\SiteSettingsService::class)->get('contact_form', []);
    $contactFieldDefaults = array_fill_keys(array_keys(\App\Http\Controllers\Admin\ContactFormSettingsController::OPTIONAL_FIXED_FIELDS), true);
    $contactVisibleFields = array_merge($contactFieldDefaults, $contactFormSettings['fields'] ?? []);
    $contactCustomFields = $contactFormSettings['custom_fields'] ?? [];

    $contactProps = [
        'subjects' => $s['contact_section']['subjects'],
        'endpoint' => $demoMode ? null : route('contact.store'),
        'demoMode' => $demoMode,
        'whatsapp' => $wa,
        'visibleFields' => $contactVisibleFields,
        'customFields' => $contactCustomFields,
    ];
    $scheduleCallProps = [
        'subjects' => $s['contact_section']['subjects'],
        'endpoint' => $demoMode ? null : route('contact.store'),
        'demoMode' => $demoMode,
        'whatsapp' => $wa,
        'subject'  => $s['contact_section']['subjects'][0] ?? null,
        'visibleFields' => $contactVisibleFields,
        'customFields' => $contactCustomFields,
    ];

    // Foto de "Sobre mí": la subida desde /admin/about-photo tiene prioridad
    // sobre la de config/site.php (mismo orden de prioridad que el logo).
    $aboutPhotoOverride = app(\App\Services\SiteSettingsService::class)->get('about_photo');
    $aboutPhotoUrl = ! empty($aboutPhotoOverride['path'])
        ? \Illuminate\Support\Facades\Storage::url($aboutPhotoOverride['path'])
        : asset($s['about']['photo']);

    // Galería del carrusel de inicio: overrides de /admin/gallery si existen,
    // si no las imágenes de ejemplo de config/site.php. Cada ruta se resuelve
    // con asset() (imágenes de fábrica en public/images/) o Storage::url()
    // (imágenes subidas por la administradora).
    $galleryOverride = app(\App\Services\SiteSettingsService::class)->get('gallery');
    $galleryImages = collect($galleryOverride['images'] ?? $s['gallery']['images'])
        ->map(fn ($img) => [
            'url' => str_starts_with($img['path'], 'gallery/')
                ? \Illuminate\Support\Facades\Storage::url($img['path'])
                : asset($img['path']),
            'alt' => $img['alt'] ?: $s['name'],
        ])
        ->values()
        ->all();
    // Antes ocupaba todo el ancho de la página en 16:9 (se veía
    // desproporcionadamente grande); ahora usa una proporción más compacta,
    // a juego con el contenedor angosto en el que se muestra.
    $galleryProps = ['images' => $galleryImages, 'aspectClass' => 'aspect-[4/3]'];

    // Visibilidad de secciones: el admin puede ocultar (sin borrar) cualquier
    // sección "hideable" desde /admin/section-visibility. Ausencia de key en
    // lo guardado = visible (comportamiento de fábrica).
    $sectionVisibility = app(\App\Services\SiteSettingsService::class)->get('section_visibility', []);
    $isSectionVisible = fn (string $key) => $sectionVisibility[$key] ?? true;

    // Promociones activas y todavía vigentes (scopeActive ya filtra por
    // is_active y valid_until), en el orden definido por el admin.
    $activePromotions = $demoMode ? collect() : \App\Models\Promotion::active()->ordered()->get();
@endphp

@section('content')

{{-- ============ HERO ============ --}}
<section class="relative overflow-hidden bg-gradient-to-b from-paper-50 to-sky-50">
    <div class="section-glow -right-32 -top-32 size-96 bg-sky-100"></div>
    <div class="section-glow -bottom-40 -left-32 size-96 bg-paper-200"></div>
    <div class="section-glow right-1/4 top-1/3 size-56 bg-clay-400/20"></div>

    <div class="container-x relative grid gap-12 py-20 md:grid-cols-[1.1fr_0.9fr] md:items-center md:py-28">
        <div>
            <p class="eyebrow">{{ $s['hero']['kicker'] }}</p>
            <h1 class="mt-4 text-4xl leading-[1.1] sm:text-5xl md:text-6xl">
                {{ $s['hero']['title'] }}
            </h1>
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-soft">
                {{ $s['hero']['subtitle'] }}
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary">
                    {{ $s['hero']['cta_primary'] }}
                </a>
                <div
                    data-vue="ScheduleCallModal"
                    data-props="{{ json_encode($scheduleCallProps + ['triggerLabel' => $s['hero']['cta_secondary']], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                ></div>
                {{-- "Agendar cita" es un flujo distinto de la llamada gratis de
                     arriba: exige cuenta de paciente y elegir método de pago,
                     por eso lleva a /perfil/citas (que ya exige login) o, si
                     no hay sesión, directo al registro. --}}
                <a href="{{ Auth::check() ? route('patient.appointments.index') : route('register') }}" class="btn btn-ghost">
                    Agendar una cita
                </a>
            </div>

            <dl class="mt-12 grid gap-6 sm:grid-cols-3">
                @foreach ($s['hero']['points'] as $point)
                    <div>
                        <dt class="font-serif text-base text-sky-800">{{ $point['title'] }}</dt>
                        <dd class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $point['text'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="relative">
            <div class="mx-auto max-w-sm overflow-hidden rounded-[2rem] border border-paper-200 bg-sky-100 shadow-xl transition-transform duration-500 hover:-translate-y-1">
                <img
                    src="{{ $aboutPhotoUrl }}"
                    alt="{{ $s['name'] }}, {{ $s['role'] }}"
                    class="aspect-[4/5] w-full object-cover"
                    onerror="this.style.display='none'; this.parentElement.classList.add('grid','place-items-center','aspect-[4/5]');"
                >
            </div>
            <div class="absolute -bottom-5 left-1/2 -translate-x-1/2 rounded-full border border-paper-200 bg-card-fixed px-5 py-2.5 text-center text-xs font-bold text-on-card-fixed shadow-lg">
                {{ $s['registration'] }}
            </div>
        </div>
    </div>
</section>

{{-- ============ GALERÍA ============ --}}
@if (!empty($galleryImages) && $isSectionVisible('gallery'))
    <section class="section !py-12 bg-paper-50 sm:!py-16">
        <div class="container-x">
            <div class="mx-auto max-w-xl text-center">
                <p class="eyebrow">Espacio de trabajo</p>
                <h2 class="mt-3 text-2xl sm:text-3xl">Un espacio pensado para ti</h2>
            </div>
            <div class="mx-auto mt-8 max-w-xl overflow-hidden rounded-[2rem] border border-paper-200 shadow-xl">
                <div
                    data-vue="Carousel"
                    data-props="{{ json_encode($galleryProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                ></div>
            </div>
        </div>
    </section>
@endif

{{-- ============ SOBRE MÍ ============ --}}
@if ($isSectionVisible('about'))
<section id="sobre-mi" class="section bg-paper-alt">
    <div class="container-x grid gap-12 md:grid-cols-[0.9fr_1.1fr] md:items-start">
        <div>
            <p class="eyebrow">Quién te acompaña</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['about']['title'] }}</h2>
            <ul class="mt-8 space-y-3">
                @foreach ($s['about']['credentials'] as $c)
                    <li class="flex items-center gap-3 text-sm font-semibold text-sky-700">
                        <span class="grid size-6 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-600">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        {{ $c }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="prose-soft">
            <p class="font-serif text-xl leading-relaxed text-sky-800">{{ $s['about']['lead'] }}</p>
            @foreach ($s['about']['paragraphs'] as $p)
                <p class="mt-4">{{ $p }}</p>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ CÓMO TE AYUDO ============ --}}
@if ($isSectionVisible('services'))
<section id="servicios" class="section bg-paper-50">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Cómo te ayudo</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['services']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['services']['subtitle'] }}</p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($s['services']['items'] as $item)
                <div class="card reveal reveal-delay-{{ ($loop->index % 6) + 1 }}">
                    <span class="grid size-11 place-items-center rounded-xl bg-sky-100 text-sky-600">
                        @include('partials.icon', ['name' => $item['icon']])
                    </span>
                    <h3 class="mt-4 text-lg">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $item['text'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Cómo son las sesiones --}}
        <div class="mt-12 rounded-3xl border border-paper-200 bg-card-fixed p-8">
            <h3 class="text-xl !text-on-card-fixed">{{ $s['sessions']['title'] }}</h3>
            <dl class="mt-6 grid gap-6 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($s['sessions']['items'] as $item)
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-on-card-fixed-soft">{{ $item['label'] }}</dt>
                        <dd class="mt-1 font-serif text-lg text-on-card-fixed">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</section>
@endif

{{-- ============ BENEFICIOS ============ --}}
@if ($isSectionVisible('benefits'))
<section id="beneficios" class="section bg-paper-alt">
    <div class="container-x grid gap-12 md:grid-cols-[0.9fr_1.1fr] md:items-start">
        <div>
            <p class="eyebrow">Beneficios</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['benefits']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['benefits']['subtitle'] }}</p>
            <a href="#contacto" class="btn btn-primary mt-8">Quiero empezar</a>
        </div>
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($s['benefits']['items'] as $item)
                <li class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex gap-3 rounded-2xl border border-paper-200 bg-paper-50 p-4 transition-colors hover:border-sky-300">
                    <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-sky-600 text-on-dark">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="text-sm leading-relaxed text-ink">{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

{{-- ============ PROMOCIONES Y PLANES ============ --}}
@if ($activePromotions->isNotEmpty() && $isSectionVisible('promotions'))
<section id="promociones" class="section bg-paper-50">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Promociones</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">Planes pensados para ti</h2>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($activePromotions as $promotion)
                <div class="card reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex flex-col">
                    <h3 class="text-lg">{{ $promotion->title }}</h3>
                    <p class="mt-2 font-serif text-3xl text-sky-700">${{ number_format($promotion->price, 2) }}</p>
                    <p class="mt-3 grow text-sm leading-relaxed text-ink-soft">{{ $promotion->description }}</p>
                    @if ($promotion->valid_until)
                        <p class="mt-3 text-xs text-ink-soft">Vigente hasta {{ $promotion->valid_until->format('d/m/Y') }}</p>
                    @endif
                    <a
                        href="{{ Auth::check() ? route('patient.appointments.index', ['promotion' => $promotion->id]) : route('register', ['promotion' => $promotion->id]) }}"
                        class="btn btn-primary mt-5"
                    >
                        Elegir este plan
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ TERAPIA EMDR ============ --}}
@if ($isSectionVisible('emdr'))
<section id="emdr" class="section relative overflow-hidden bg-ink-panel text-on-dark-soft">
    <div class="section-glow -right-24 -top-24 size-80 bg-sky-600/40"></div>
    <div class="section-glow -bottom-32 left-1/4 size-96 bg-clay-400/10"></div>

    <div class="container-x relative">
        <div class="max-w-2xl">
            <p class="eyebrow text-on-dark-soft">Método</p>
            <h2 class="mt-3 text-3xl text-on-dark sm:text-4xl">{{ $s['emdr']['title'] }}</h2>
            <p class="mt-5 text-lg leading-relaxed text-on-dark-soft">{{ $s['emdr']['lead'] }}</p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($s['emdr']['advantages'] as $adv)
                <div class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} rounded-2xl border border-white/10 bg-white/5 p-6 transition-colors hover:bg-white/10">
                    <h3 class="text-base text-on-dark">{{ $adv['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-on-dark-soft">{{ $adv['text'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-14">
            <h3 class="text-xl text-on-dark">Cómo funciona el proceso</h3>
            <ol class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($s['emdr']['steps'] as $step)
                    <li class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} relative rounded-2xl border border-white/10 p-6 transition-colors hover:bg-white/5">
                        <span class="font-serif text-3xl text-sky-300">{{ $step['n'] }}</span>
                        <h4 class="mt-2 text-base text-on-dark">{{ $step['title'] }}</h4>
                        <p class="mt-1 text-sm leading-relaxed text-on-dark-soft">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>

        <div
            class="mt-12"
            data-vue="ScheduleCallModal"
            data-props="{{ json_encode($scheduleCallProps + ['triggerLabel' => 'Reserva tu llamada gratuita', 'triggerClass' => 'btn bg-on-dark text-ink-panel hover:opacity-90'], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
        ></div>
    </div>
</section>
@endif

{{-- ============ TESTIMONIOS ============ --}}
@if ($isSectionVisible('testimonials'))
<section id="testimonios" class="section bg-paper-50">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Testimonios</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['testimonials']['title'] }}</h2>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-3">
            @foreach (array_merge($s['testimonials']['items'], ($patientTestimonials ?? collect())->toArray()) as $t)
                @php $rating = $t['rating'] ?? 5; @endphp
                <figure class="card reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex flex-col">
                    <div class="mb-4 flex gap-1 text-clay-400">
                        @for ($i = 0; $i < $rating; $i++)
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.9 7.5.6-5.7 5 1.8 7.4L12 17.8 5.4 21.9 7.2 14.5 1.5 9.5 9 8.9z"/></svg>
                        @endfor
                    </div>
                    <blockquote class="grow font-serif text-lg leading-relaxed text-sky-800">
                        &ldquo;{{ $t['text'] }}&rdquo;
                    </blockquote>
                    <figcaption class="mt-5 text-sm font-bold text-ink-soft">
                        {{ $t['name'] }}
                        @if (!empty($t['place']))
                            · <span class="font-semibold">{{ $t['place'] }}</span>
                        @endif
                    </figcaption>
                </figure>
            @endforeach
        </div>
        <p class="mt-6 text-xs text-ink-soft">{{ $s['testimonials']['note'] }}</p>
    </div>
</section>
@endif

{{-- ============ MITOS ============ --}}
@if ($isSectionVisible('myths'))
<section id="mitos" class="section bg-paper-alt">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Sin estigmas</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['myths']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['myths']['subtitle'] }}</p>
        </div>
        <div class="mt-12" data-vue="MythCards" data-props="{{ json_encode($mythProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
    </div>
</section>
@endif

{{-- ============ CHEQUEO EMOCIONAL ============ --}}
@if ($isSectionVisible('checkup'))
<section id="chequeo" class="section bg-paper-50">
    <div class="container-x grid gap-12 md:grid-cols-[0.85fr_1.15fr] md:items-start">
        <div>
            <p class="eyebrow">Herramienta gratuita</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['checkup']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['checkup']['subtitle'] }}</p>
        </div>
        <div
            data-vue="EmotionalCheckup"
            data-props="{{ json_encode($checkupProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
        ></div>
    </div>
</section>
@endif

{{-- ============ FAQ ============ --}}
@if ($isSectionVisible('faq'))
<section id="faq" class="section bg-paper-alt">
    <div class="container-x grid gap-12 md:grid-cols-[0.7fr_1.3fr] md:items-start">
        <div>
            <p class="eyebrow">Dudas</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['faq']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">
                ¿Tienes otra pregunta? Escríbeme y te la resuelvo sin compromiso.
            </p>
        </div>
        <div data-vue="FaqAccordion" data-props="{{ json_encode($faqProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
    </div>
</section>
@endif

{{-- ============ SECCIONES PERSONALIZADAS ============ --}}
@foreach ($customSections ?? [] as $customSection)
    @include('partials.custom-section', ['section' => $customSection])
@endforeach

{{-- ============ CONTACTO ============ --}}
@if ($isSectionVisible('contact_section'))
<section id="contacto" class="section bg-paper-50">
    <div class="container-x grid gap-12 md:grid-cols-[0.85fr_1.15fr] md:items-start">
        <div>
            <p class="eyebrow">Contacto</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['contact_section']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['contact_section']['subtitle'] }}</p>

            <div class="mt-8 space-y-4">
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-2xl border border-paper-200 bg-card-fixed p-4 transition-colors hover:border-sky-300">
                    <span class="grid size-10 place-items-center rounded-full bg-sky-100 text-sky-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.5A10 10 0 1012 2zm0 18a8 8 0 01-4.1-1.1l-.3-.2-2.8.9.9-2.7-.2-.3A8 8 0 1112 20zm4.4-5.6c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.5 6.5 0 01-1.9-1.2 7.2 7.2 0 01-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.5.3-.5v-.5c0-.1-.6-1.5-.8-2s-.4-.4-.6-.4h-.5a1 1 0 00-.7.3A2.9 2.9 0 006 6.6c0 1.7 1.3 3.4 1.5 3.6s2.5 3.9 6.1 5.3c2.2.8 2.7.7 3.2.6s1.4-.6 1.6-1.1a2 2 0 00.1-1.1c0-.2-.2-.3-.4-.4z"/></svg>
                    </span>
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-on-card-fixed-soft">WhatsApp</span>
                        <span class="block text-sm font-semibold text-on-card-fixed">{{ $s['contact']['whatsapp_show'] }}</span>
                    </span>
                </a>
                <a href="mailto:{{ $s['contact']['email'] }}" class="flex items-center gap-3 rounded-2xl border border-paper-200 bg-card-fixed p-4 transition-colors hover:border-sky-300">
                    <span class="grid size-10 place-items-center rounded-full bg-sky-100 text-sky-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    </span>
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-on-card-fixed-soft">Email</span>
                        <span class="block text-sm font-semibold text-on-card-fixed">{{ $s['contact']['email'] }}</span>
                    </span>
                </a>
                <div class="rounded-2xl border border-paper-200 bg-card-fixed p-4 text-sm text-on-card-fixed-soft">
                    <p><strong class="text-on-card-fixed">Zona de atención:</strong> {{ $s['contact']['area'] }}</p>
                    <p class="mt-1">{{ $s['contact']['response'] }}</p>
                </div>

                <div class="pt-2">
                    @include('partials.social-icons', ['heading' => 'Sígueme en redes'])
                </div>
            </div>
        </div>

        <div
            data-vue="ContactForm"
            data-props="{{ json_encode($contactProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
        ></div>
    </div>
</section>
@endif

@endsection
