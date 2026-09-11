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
    $contactProps = [
        'subjects' => $s['contact_section']['subjects'],
        'endpoint' => $demoMode ? null : route('contact.store'),
        'demoMode' => $demoMode,
        'whatsapp' => $wa,
    ];
    $scheduleCallProps = [
        'subjects' => $s['contact_section']['subjects'],
        'endpoint' => $demoMode ? null : route('contact.store'),
        'demoMode' => $demoMode,
        'whatsapp' => $wa,
        'subject'  => $s['contact_section']['subjects'][0] ?? null,
    ];
@endphp

@section('content')

{{-- ============ HERO ============ --}}
<section class="relative overflow-hidden bg-paper-50">
    <div class="pointer-events-none absolute -right-32 -top-32 size-96 rounded-full bg-sky-100 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 -left-32 size-96 rounded-full bg-paper-200 blur-3xl"></div>

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
            <div class="mx-auto max-w-sm overflow-hidden rounded-[2rem] border border-paper-200 bg-sky-100 shadow-xl">
                <img
                    src="{{ asset($s['about']['photo']) }}"
                    alt="{{ $s['name'] }}, {{ $s['role'] }}"
                    class="aspect-[4/5] w-full object-cover"
                    onerror="this.style.display='none'; this.parentElement.classList.add('grid','place-items-center','aspect-[4/5]');"
                >
            </div>
            <div class="absolute -bottom-5 left-1/2 -translate-x-1/2 rounded-full border border-paper-200 bg-white px-5 py-2.5 text-center text-xs font-bold text-sky-700 shadow-lg">
                {{ $s['registration'] }}
            </div>
        </div>
    </div>
</section>

{{-- ============ SOBRE MÍ ============ --}}
<section id="sobre-mi" class="section bg-white">
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

{{-- ============ CÓMO TE AYUDO ============ --}}
<section id="servicios" class="section bg-paper-50">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Cómo te ayudo</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['services']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['services']['subtitle'] }}</p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($s['services']['items'] as $item)
                <div class="card">
                    <span class="grid size-11 place-items-center rounded-xl bg-sky-100 text-sky-600">
                        @include('partials.icon', ['name' => $item['icon']])
                    </span>
                    <h3 class="mt-4 text-lg">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $item['text'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Cómo son las sesiones --}}
        <div class="mt-12 rounded-3xl border border-paper-200 bg-white p-8">
            <h3 class="text-xl">{{ $s['sessions']['title'] }}</h3>
            <dl class="mt-6 grid gap-6 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($s['sessions']['items'] as $item)
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-sky-500">{{ $item['label'] }}</dt>
                        <dd class="mt-1 font-serif text-lg text-sky-800">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</section>

{{-- ============ BENEFICIOS ============ --}}
<section id="beneficios" class="section bg-white">
    <div class="container-x grid gap-12 md:grid-cols-[0.9fr_1.1fr] md:items-start">
        <div>
            <p class="eyebrow">Beneficios</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['benefits']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['benefits']['subtitle'] }}</p>
            <a href="#contacto" class="btn btn-primary mt-8">Quiero empezar</a>
        </div>
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($s['benefits']['items'] as $item)
                <li class="flex gap-3 rounded-2xl border border-paper-200 bg-paper-50 p-4">
                    <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-sky-600 text-paper-50">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="text-sm leading-relaxed text-ink">{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ============ TERAPIA EMDR ============ --}}
<section id="emdr" class="section bg-sky-800 text-paper-100">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow text-paper-200">Método</p>
            <h2 class="mt-3 text-3xl text-paper-50 sm:text-4xl">{{ $s['emdr']['title'] }}</h2>
            <p class="mt-5 text-lg leading-relaxed text-paper-200">{{ $s['emdr']['lead'] }}</p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($s['emdr']['advantages'] as $adv)
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h3 class="text-base text-paper-50">{{ $adv['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-paper-200">{{ $adv['text'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-14">
            <h3 class="text-xl text-paper-50">Cómo funciona el proceso</h3>
            <ol class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($s['emdr']['steps'] as $step)
                    <li class="relative rounded-2xl border border-white/10 p-6">
                        <span class="font-serif text-3xl text-sky-300">{{ $step['n'] }}</span>
                        <h4 class="mt-2 text-base text-paper-50">{{ $step['title'] }}</h4>
                        <p class="mt-1 text-sm leading-relaxed text-paper-200">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>

        <div
            class="mt-12"
            data-vue="ScheduleCallModal"
            data-props="{{ json_encode($scheduleCallProps + ['triggerLabel' => 'Reserva tu llamada gratuita', 'triggerClass' => 'btn bg-paper-50 text-sky-800 hover:bg-white'], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
        ></div>
    </div>
</section>

{{-- ============ TESTIMONIOS ============ --}}
<section id="testimonios" class="section bg-paper-50">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Testimonios</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['testimonials']['title'] }}</h2>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-3">
            @foreach ($s['testimonials']['items'] as $t)
                <figure class="card flex flex-col">
                    <div class="mb-4 flex gap-1 text-clay-400">
                        @for ($i = 0; $i < 5; $i++)
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.9 7.5.6-5.7 5 1.8 7.4L12 17.8 5.4 21.9 7.2 14.5 1.5 9.5 9 8.9z"/></svg>
                        @endfor
                    </div>
                    <blockquote class="grow font-serif text-lg leading-relaxed text-sky-800">
                        &ldquo;{{ $t['text'] }}&rdquo;
                    </blockquote>
                    <figcaption class="mt-5 text-sm font-bold text-ink-soft">
                        {{ $t['name'] }} · <span class="font-semibold">{{ $t['place'] }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
        <p class="mt-6 text-xs text-ink-soft">{{ $s['testimonials']['note'] }}</p>
    </div>
</section>

{{-- ============ MITOS ============ --}}
<section id="mitos" class="section bg-white">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Sin estigmas</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['myths']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['myths']['subtitle'] }}</p>
        </div>
        <div class="mt-12" data-vue="MythCards" data-props="{{ json_encode($mythProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
    </div>
</section>

{{-- ============ CHEQUEO EMOCIONAL ============ --}}
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

{{-- ============ FAQ ============ --}}
<section id="faq" class="section bg-white">
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

{{-- ============ CONTACTO ============ --}}
<section id="contacto" class="section bg-paper-50">
    <div class="container-x grid gap-12 md:grid-cols-[0.85fr_1.15fr] md:items-start">
        <div>
            <p class="eyebrow">Contacto</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['contact_section']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['contact_section']['subtitle'] }}</p>

            <div class="mt-8 space-y-4">
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-2xl border border-paper-200 bg-white p-4 transition-colors hover:border-sky-300">
                    <span class="grid size-10 place-items-center rounded-full bg-sky-100 text-sky-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.5A10 10 0 1012 2zm0 18a8 8 0 01-4.1-1.1l-.3-.2-2.8.9.9-2.7-.2-.3A8 8 0 1112 20zm4.4-5.6c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.5 6.5 0 01-1.9-1.2 7.2 7.2 0 01-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.5.3-.5v-.5c0-.1-.6-1.5-.8-2s-.4-.4-.6-.4h-.5a1 1 0 00-.7.3A2.9 2.9 0 006 6.6c0 1.7 1.3 3.4 1.5 3.6s2.5 3.9 6.1 5.3c2.2.8 2.7.7 3.2.6s1.4-.6 1.6-1.1a2 2 0 00.1-1.1c0-.2-.2-.3-.4-.4z"/></svg>
                    </span>
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-sky-500">WhatsApp</span>
                        <span class="block text-sm font-semibold text-ink">{{ $s['contact']['whatsapp_show'] }}</span>
                    </span>
                </a>
                <a href="mailto:{{ $s['contact']['email'] }}" class="flex items-center gap-3 rounded-2xl border border-paper-200 bg-white p-4 transition-colors hover:border-sky-300">
                    <span class="grid size-10 place-items-center rounded-full bg-sky-100 text-sky-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    </span>
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-sky-500">Email</span>
                        <span class="block text-sm font-semibold text-ink">{{ $s['contact']['email'] }}</span>
                    </span>
                </a>
                <div class="rounded-2xl border border-paper-200 bg-white p-4 text-sm text-ink-soft">
                    <p><strong class="text-sky-700">Zona de atención:</strong> {{ $s['contact']['area'] }}</p>
                    <p class="mt-1">{{ $s['contact']['response'] }}</p>
                </div>

                <div class="pt-2">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wider text-sky-500">Sígueme en redes</p>
                    @include('partials.social-icons')
                </div>
            </div>
        </div>

        <div
            data-vue="ContactForm"
            data-props="{{ json_encode($contactProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
        ></div>
    </div>
</section>

@endsection
