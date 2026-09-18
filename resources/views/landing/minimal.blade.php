@extends('layouts.app')

{{--
    Plantilla "Minimalista" — mismo contenido y componentes Vue que la
    plantilla clásica (resources/views/landing/classic.blade.php), pero con
    una estructura más ligera y directa: hero centrado sin foto lateral (la
    foto pasa a la sección "Sobre mí"), servicios/beneficios como lista de
    pasos numerados en vez de grid de tarjetas, testimonios en una sola
    columna, y FAQ + contacto combinados en una única sección final.
--}}

@section('content')

{{-- ============ HERO ============ --}}
<section class="relative overflow-hidden bg-paper-50">
    <div class="section-glow -right-32 -top-32 size-96 bg-sky-100"></div>

    <div class="container-x relative py-24 text-center md:py-32">
        <p class="eyebrow mx-auto">{{ $s['hero']['kicker'] }}</p>
        <h1 class="mx-auto mt-4 max-w-3xl text-4xl leading-[1.1] sm:text-5xl md:text-6xl">
            {{ $s['hero']['title'] }}
        </h1>
        <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-ink-soft">
            {{ $s['hero']['subtitle'] }}
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary">
                {{ $s['hero']['cta_primary'] }}
            </a>
            @if ($hasAvailableCallSlots)
                <div
                    data-vue="ScheduleCallModal"
                    data-props="{{ json_encode($scheduleCallProps + ['triggerLabel' => $s['hero']['cta_secondary']], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                ></div>
            @endif
            @if ($hasAvailableAppointmentSlots)
                <a href="{{ Auth::check() ? route('patient.appointments.index') : route('register') }}" class="btn btn-ghost">
                    Agendar una cita
                </a>
            @endif
        </div>

        @if ($voiceRegistrationEnabled)
            <details class="mx-auto mt-6 max-w-md rounded-2xl border border-paper-200 bg-card-fixed p-4 text-left text-sm">
                <summary class="cursor-pointer font-semibold text-sky-700">
                    📞 ¿Prefieres registrarte con una llamada?
                </summary>
                <p class="mt-2 text-on-card-fixed-soft">
                    Déjanos tu teléfono y te llamamos ahora mismo — un asistente te ayuda a crear tu
                    cuenta por voz, sin llenar ningún formulario.
                </p>
                <form
                    class="mt-3 flex flex-wrap gap-2"
                    onsubmit="event.preventDefault();
                        const form = event.target;
                        const button = form.querySelector('button');
                        const status = form.nextElementSibling;
                        button.disabled = true;
                        status.textContent = 'Enviando…';
                        fetch('{{ route('voice-registration.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ phone_number: form.phone_number.value }),
                        })
                            .then(async (response) => ({ ok: response.ok, data: await response.json() }))
                            .then(({ data }) => { status.textContent = data.message; })
                            .catch(() => { status.textContent = 'No se pudo iniciar la llamada. Intenta de nuevo en unos minutos.'; })
                            .finally(() => { button.disabled = false; });"
                >
                    <input
                        type="tel" name="phone_number" required maxlength="30"
                        placeholder="Ej. 61079711"
                        class="min-w-0 flex-1 rounded-xl border border-paper-200 bg-paper-50 px-3 py-2 text-sm outline-none focus:border-sky-400"
                    >
                    <button type="submit" class="btn btn-primary text-sm">Llamarme</button>
                </form>
                <p class="mt-2 text-xs text-on-card-fixed-soft" role="status"></p>
            </details>
        @endif

        <dl class="mx-auto mt-14 grid max-w-2xl gap-6 sm:grid-cols-3">
            @foreach ($s['hero']['points'] as $point)
                <div>
                    <dt class="font-serif text-base text-sky-800">{{ $point['title'] }}</dt>
                    <dd class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $point['text'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

{{-- ============ GALERÍA ============ --}}
@if (!empty($galleryImages) && $isSectionVisible('gallery'))
    <section class="section !py-12 bg-paper-alt sm:!py-16">
        <div class="container-x">
            <div class="mx-auto max-w-xl overflow-hidden rounded-[2rem] border border-paper-200 shadow-xl">
                <div
                    data-vue="Carousel"
                    data-props="{{ json_encode($galleryProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                ></div>
            </div>
        </div>
    </section>
@endif

{{-- ============ SOBRE MÍ (con foto, movida aquí en vez del hero) ============ --}}
@if ($isSectionVisible('about'))
<section id="sobre-mi" class="section bg-paper-50">
    <div class="container-x mx-auto max-w-3xl text-center">
        <div class="mx-auto max-w-[10rem] overflow-hidden rounded-full border border-paper-200 shadow-lg">
            <img
                src="{{ $aboutPhotoUrl }}"
                alt="{{ $s['name'] }}, {{ $s['role'] }}"
                class="aspect-square w-full object-cover"
                onerror="this.style.display='none';"
            >
        </div>
        <p class="eyebrow mx-auto mt-6">Quién te acompaña</p>
        <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['about']['title'] }}</h2>
        <p class="mx-auto mt-5 max-w-xl font-serif text-xl leading-relaxed text-sky-800">{{ $s['about']['lead'] }}</p>
        <div class="prose-soft mx-auto mt-4 max-w-xl text-left">
            @foreach ($s['about']['paragraphs'] as $p)
                <p class="mt-4">{{ $p }}</p>
            @endforeach
        </div>
        <ul class="mx-auto mt-8 flex max-w-xl flex-wrap justify-center gap-x-6 gap-y-2">
            @foreach ($s['about']['credentials'] as $c)
                <li class="flex items-center gap-2 text-sm font-semibold text-sky-700">
                    <span class="grid size-5 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-600">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    {{ $c }}
                </li>
            @endforeach
        </ul>
        <div class="mx-auto mt-10 max-w-xl rounded-3xl border border-paper-200 bg-card-fixed p-8 text-left">
            <h3 class="text-xl !text-on-card-fixed">{{ $s['sessions']['title'] }}</h3>
            <dl class="mt-6 grid gap-6 sm:grid-cols-3">
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

{{-- ============ CÓMO TE AYUDO (lista de pasos numerados, no grid) ============ --}}
@if ($isSectionVisible('services'))
<section id="servicios" class="section bg-paper-alt">
    <div class="container-x mx-auto max-w-3xl">
        <div class="text-center">
            <p class="eyebrow mx-auto">Cómo te ayudo</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['services']['title'] }}</h2>
            <p class="mx-auto mt-4 max-w-xl leading-relaxed text-ink-soft">{{ $s['services']['subtitle'] }}</p>
        </div>

        <ol class="mt-12 space-y-6">
            @foreach ($s['services']['items'] as $item)
                <li class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex gap-5 rounded-2xl border border-paper-200 bg-paper-50 p-5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-600">
                        @include('partials.icon', ['name' => $item['icon']])
                    </span>
                    <div>
                        <h3 class="text-lg">{{ $item['title'] }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $item['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endif

{{-- ============ BENEFICIOS (lista, no columnas) ============ --}}
@if ($isSectionVisible('benefits'))
<section id="beneficios" class="section bg-paper-50">
    <div class="container-x mx-auto max-w-3xl text-center">
        <p class="eyebrow mx-auto">Beneficios</p>
        <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['benefits']['title'] }}</h2>
        <p class="mx-auto mt-4 max-w-xl leading-relaxed text-ink-soft">{{ $s['benefits']['subtitle'] }}</p>

        <ul class="mx-auto mt-10 max-w-xl space-y-3 text-left">
            @foreach ($s['benefits']['items'] as $item)
                <li class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex gap-3">
                    <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-sky-600 text-on-dark">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="text-sm leading-relaxed text-ink">{{ $item }}</span>
                </li>
            @endforeach
        </ul>
        <a href="#contacto" class="btn btn-primary mt-8">Quiero empezar</a>
    </div>
</section>
@endif

{{-- ============ PROMOCIONES Y PLANES ============ --}}
@if ($activePromotions->isNotEmpty() && $isSectionVisible('promotions'))
<section id="promociones" class="section bg-paper-alt">
    <div class="container-x mx-auto max-w-3xl text-center">
        <p class="eyebrow mx-auto">Promociones</p>
        <h2 class="mt-3 text-3xl sm:text-4xl">Planes pensados para ti</h2>

        <div class="mx-auto mt-10 max-w-xl space-y-4 text-left">
            @foreach ($activePromotions as $promotion)
                <div class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-paper-200 bg-paper-50 p-5">
                    <div>
                        <h3 class="text-lg">{{ $promotion->title }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $promotion->description }}</p>
                        @if ($promotion->valid_until)
                            <p class="mt-1 text-xs text-ink-soft">Vigente hasta {{ $promotion->valid_until->format('d/m/Y') }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-serif text-2xl text-sky-700">${{ number_format($promotion->price, 2) }}</p>
                        <a
                            href="{{ Auth::check() ? route('patient.appointments.index', ['promotion' => $promotion->id]) : route('register', ['promotion' => $promotion->id]) }}"
                            class="btn btn-primary mt-2 text-sm"
                        >
                            Elegir
                        </a>
                    </div>
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

    <div class="container-x relative mx-auto max-w-3xl text-center">
        <p class="eyebrow text-on-dark-soft mx-auto">Método</p>
        <h2 class="mt-3 text-3xl text-on-dark sm:text-4xl">{{ $s['emdr']['title'] }}</h2>
        <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-on-dark-soft">{{ $s['emdr']['lead'] }}</p>

        <ol class="mx-auto mt-12 max-w-xl space-y-4 text-left">
            @foreach ($s['emdr']['steps'] as $step)
                <li class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex gap-4 rounded-2xl border border-white/10 p-5 transition-colors hover:bg-white/5">
                    <span class="font-serif text-2xl text-sky-300">{{ $step['n'] }}</span>
                    <div>
                        <h4 class="text-base text-on-dark">{{ $step['title'] }}</h4>
                        <p class="mt-1 text-sm leading-relaxed text-on-dark-soft">{{ $step['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <ul class="mx-auto mt-10 flex max-w-xl flex-wrap justify-center gap-3 text-sm text-on-dark-soft">
            @foreach ($s['emdr']['advantages'] as $adv)
                <li class="rounded-full border border-white/10 px-4 py-2">{{ $adv['title'] }}</li>
            @endforeach
        </ul>

        @if ($hasAvailableCallSlots)
            <div
                class="mt-10 flex justify-center"
                data-vue="ScheduleCallModal"
                data-props="{{ json_encode($scheduleCallProps + ['triggerLabel' => 'Reserva tu llamada gratuita', 'triggerClass' => 'btn bg-on-dark text-ink-panel hover:opacity-90'], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
            ></div>
        @endif
    </div>
</section>
@endif

{{-- ============ TESTIMONIOS (una columna) ============ --}}
@if ($isSectionVisible('testimonials'))
<section id="testimonios" class="section bg-paper-50">
    <div class="container-x mx-auto max-w-2xl text-center">
        <p class="eyebrow mx-auto">Testimonios</p>
        <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['testimonials']['title'] }}</h2>

        <div class="mt-12 space-y-8">
            @foreach (array_merge($s['testimonials']['items'], ($patientTestimonials ?? collect())->toArray()) as $t)
                @php $rating = $t['rating'] ?? 5; @endphp
                <figure class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }}">
                    <div class="mb-3 flex justify-center gap-1 text-clay-400">
                        @for ($i = 0; $i < $rating; $i++)
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.9 7.5.6-5.7 5 1.8 7.4L12 17.8 5.4 21.9 7.2 14.5 1.5 9.5 9 8.9z"/></svg>
                        @endfor
                    </div>
                    <blockquote class="font-serif text-xl leading-relaxed text-sky-800">
                        &ldquo;{{ $t['text'] }}&rdquo;
                    </blockquote>
                    <figcaption class="mt-4 text-sm font-bold text-ink-soft">
                        {{ $t['name'] }}
                        @if (!empty($t['place']))
                            · <span class="font-semibold">{{ $t['place'] }}</span>
                        @endif
                    </figcaption>
                </figure>
            @endforeach
        </div>
        <p class="mt-8 text-xs text-ink-soft">{{ $s['testimonials']['note'] }}</p>
    </div>
</section>
@endif

{{-- ============ MITOS ============ --}}
@if ($isSectionVisible('myths'))
<section id="mitos" class="section bg-paper-alt">
    <div class="container-x mx-auto max-w-3xl text-center">
        <p class="eyebrow mx-auto">Sin estigmas</p>
        <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['myths']['title'] }}</h2>
        <p class="mx-auto mt-4 max-w-xl leading-relaxed text-ink-soft">{{ $s['myths']['subtitle'] }}</p>
        <div class="mt-10 text-left" data-vue="MythCards" data-props="{{ json_encode($mythProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
    </div>
</section>
@endif

{{-- ============ CHEQUEO EMOCIONAL ============ --}}
@if ($isSectionVisible('checkup'))
<section id="chequeo" class="section bg-paper-50">
    <div class="container-x mx-auto max-w-2xl text-center">
        <p class="eyebrow mx-auto">Herramienta gratuita</p>
        <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['checkup']['title'] }}</h2>
        <p class="mx-auto mt-4 max-w-xl leading-relaxed text-ink-soft">{{ $s['checkup']['subtitle'] }}</p>
        <div class="mt-10 text-left" data-vue="EmotionalCheckup" data-props="{{ json_encode($checkupProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
    </div>
</section>
@endif

{{-- ============ SECCIONES PERSONALIZADAS ============ --}}
@foreach ($customSections ?? [] as $customSection)
    @include('partials.custom-section', ['section' => $customSection])
@endforeach

{{-- ============ FAQ + CONTACTO combinados ============ --}}
@if ($isSectionVisible('faq') || $isSectionVisible('contact_section'))
<section id="faq" class="section bg-paper-alt">
    <div class="container-x grid gap-14 md:grid-cols-2">
        @if ($isSectionVisible('faq'))
            <div>
                <p class="eyebrow">Dudas</p>
                <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['faq']['title'] }}</h2>
                <div class="mt-6" data-vue="FaqAccordion" data-props="{{ json_encode($faqProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
            </div>
        @endif

        @if ($isSectionVisible('contact_section'))
            <div id="contacto">
                <p class="eyebrow">Contacto</p>
                <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['contact_section']['title'] }}</h2>
                <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['contact_section']['subtitle'] }}</p>

                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="rounded-full border border-paper-200 bg-card-fixed px-4 py-2 font-semibold text-on-card-fixed hover:border-sky-300">
                        WhatsApp
                    </a>
                    @if (! empty($telegramUsername))
                        <a href="https://t.me/{{ $telegramUsername }}" target="_blank" rel="noopener" class="rounded-full border border-paper-200 bg-card-fixed px-4 py-2 font-semibold text-on-card-fixed hover:border-sky-300">
                            Telegram
                        </a>
                    @endif
                    <a href="mailto:{{ $s['contact']['email'] }}" class="rounded-full border border-paper-200 bg-card-fixed px-4 py-2 font-semibold text-on-card-fixed hover:border-sky-300">
                        Email
                    </a>
                </div>

                <div class="mt-6">
                    @include('partials.social-icons', ['heading' => 'Sígueme en redes'])
                </div>

                <div
                    class="mt-8"
                    data-vue="ContactForm"
                    data-props="{{ json_encode($contactProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                ></div>
            </div>
        @endif
    </div>
</section>
@endif

@endsection
