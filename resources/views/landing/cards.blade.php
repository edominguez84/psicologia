@extends('layouts.app')

{{--
    Plantilla "Tarjetas" — mismo contenido y componentes Vue que la
    plantilla clásica (resources/views/landing/classic.blade.php), pero con
    hero de imagen de fondo a pantalla completa, promociones destacadas
    justo debajo del hero, y el resto de secciones de contenido presentadas
    como grid de tarjetas grandes con sombra pronunciada.
--}}

@section('content')

{{-- ============ HERO (imagen de fondo a sangre completa) ============ --}}
<section class="relative overflow-hidden bg-ink-panel">
    <img
        src="{{ $aboutPhotoUrl }}"
        alt=""
        aria-hidden="true"
        class="absolute inset-0 size-full object-cover opacity-40"
        onerror="this.style.display='none';"
    >
    <div class="absolute inset-0 bg-gradient-to-t from-ink-panel via-ink-panel/70 to-ink-panel/20"></div>

    <div class="container-x relative py-24 text-center md:py-36">
        <p class="eyebrow mx-auto text-on-dark-soft">{{ $s['hero']['kicker'] }}</p>
        <h1 class="mx-auto mt-4 max-w-3xl text-4xl leading-[1.1] text-on-dark sm:text-5xl md:text-6xl">
            {{ $s['hero']['title'] }}
        </h1>
        <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-on-dark-soft">
            {{ $s['hero']['subtitle'] }}
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn bg-on-dark text-ink-panel hover:opacity-90">
                {{ $s['hero']['cta_primary'] }}
            </a>
            @if ($hasAvailableCallSlots)
                <div
                    data-vue="ScheduleCallModal"
                    data-props="{{ json_encode($scheduleCallProps + ['triggerLabel' => $s['hero']['cta_secondary'], 'triggerClass' => 'btn border border-white/40 text-on-dark hover:bg-white/10'], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                ></div>
            @endif
            @if ($hasAvailableAppointmentSlots)
                <a href="{{ Auth::check() ? route('patient.appointments.index') : route('register') }}" class="btn border border-white/40 text-on-dark hover:bg-white/10">
                    Agendar una cita
                </a>
            @endif
        </div>

        @if ($voiceRegistrationEnabled)
            <details class="mx-auto mt-6 max-w-md rounded-2xl border border-white/20 bg-white/10 p-4 text-left text-sm text-on-dark-soft backdrop-blur">
                <summary class="cursor-pointer font-semibold text-on-dark">
                    📞 ¿Prefieres registrarte con una llamada?
                </summary>
                <p class="mt-2">
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
                        class="min-w-0 flex-1 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-sm text-on-dark outline-none placeholder:text-on-dark-soft focus:border-white/50"
                    >
                    <button type="submit" class="btn bg-on-dark text-ink-panel text-sm hover:opacity-90">Llamarme</button>
                </form>
                <p class="mt-2 text-xs" role="status"></p>
            </details>
        @endif
    </div>
</section>

{{-- ============ PROMOCIONES (destacadas justo debajo del hero) ============ --}}
@if ($activePromotions->isNotEmpty() && $isSectionVisible('promotions'))
<section id="promociones" class="section bg-paper-50">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Promociones</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">Planes pensados para ti</h2>
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($activePromotions as $promotion)
                <div class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex flex-col rounded-3xl border border-paper-200 bg-card-fixed p-7 shadow-lg transition-transform hover:-translate-y-1">
                    <h3 class="text-xl !text-on-card-fixed">{{ $promotion->title }}</h3>
                    <p class="mt-2 font-serif text-3xl text-sky-700">${{ number_format($promotion->price, 2) }}</p>
                    <p class="mt-3 grow text-sm leading-relaxed text-on-card-fixed-soft">{{ $promotion->description }}</p>
                    @if ($promotion->valid_until)
                        <p class="mt-3 text-xs text-on-card-fixed-soft">Vigente hasta {{ $promotion->valid_until->format('d/m/Y') }}</p>
                    @endif
                    <a
                        href="{{ Auth::check() ? route('patient.appointments.index', ['promotion' => $promotion->id]) : route('register', ['promotion' => $promotion->id]) }}"
                        class="btn bg-on-dark text-ink-panel mt-5 hover:opacity-90"
                    >
                        Elegir este plan
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ GALERÍA ============ --}}
@if (!empty($galleryImages) && $isSectionVisible('gallery'))
    <section class="section !py-12 bg-paper-alt sm:!py-16">
        <div class="container-x">
            <div class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-paper-200 shadow-xl">
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
<section id="sobre-mi" class="section bg-paper-50">
    <div class="container-x">
        <div class="mx-auto max-w-3xl rounded-3xl border border-paper-200 bg-card-fixed p-8 shadow-lg sm:p-12">
            <p class="eyebrow">Quién te acompaña</p>
            <h2 class="mt-3 text-3xl !text-on-card-fixed sm:text-4xl">{{ $s['about']['title'] }}</h2>
            <p class="mt-5 font-serif text-xl leading-relaxed text-sky-700">{{ $s['about']['lead'] }}</p>
            <div class="prose-soft mt-4 !text-on-card-fixed-soft">
                @foreach ($s['about']['paragraphs'] as $p)
                    <p class="mt-4">{{ $p }}</p>
                @endforeach
            </div>
            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
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
    </div>
</section>
@endif

{{-- ============ CÓMO TE AYUDO (tarjetas grandes) ============ --}}
@if ($isSectionVisible('services'))
<section id="servicios" class="section bg-paper-alt">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Cómo te ayudo</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['services']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['services']['subtitle'] }}</p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($s['services']['items'] as $item)
                <div class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} rounded-3xl border border-paper-200 bg-paper-50 p-7 shadow-lg transition-transform hover:-translate-y-1">
                    <span class="grid size-12 place-items-center rounded-2xl bg-sky-100 text-sky-600">
                        @include('partials.icon', ['name' => $item['icon']])
                    </span>
                    <h3 class="mt-5 text-lg">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $item['text'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-10 rounded-3xl border border-paper-200 bg-card-fixed p-8 shadow-lg">
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

{{-- ============ BENEFICIOS (tarjetas) ============ --}}
@if ($isSectionVisible('benefits'))
<section id="beneficios" class="section bg-paper-50">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Beneficios</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['benefits']['title'] }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ $s['benefits']['subtitle'] }}</p>
            <a href="#contacto" class="btn btn-primary mt-8">Quiero empezar</a>
        </div>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($s['benefits']['items'] as $item)
                <div class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex items-start gap-3 rounded-3xl border border-paper-200 bg-paper-alt p-6 shadow-lg">
                    <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full bg-sky-600 text-on-dark">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="text-sm leading-relaxed text-ink">{{ $item }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ TERAPIA EMDR (tarjetas) ============ --}}
@if ($isSectionVisible('emdr'))
<section id="emdr" class="section relative overflow-hidden bg-ink-panel text-on-dark-soft">
    <div class="section-glow -right-24 -top-24 size-80 bg-sky-600/40"></div>

    <div class="container-x relative">
        <div class="max-w-2xl">
            <p class="eyebrow text-on-dark-soft">Método</p>
            <h2 class="mt-3 text-3xl text-on-dark sm:text-4xl">{{ $s['emdr']['title'] }}</h2>
            <p class="mt-5 text-lg leading-relaxed text-on-dark-soft">{{ $s['emdr']['lead'] }}</p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($s['emdr']['advantages'] as $adv)
                <div class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} rounded-2xl border border-white/10 bg-white/5 p-6 shadow-lg transition-colors hover:bg-white/10">
                    <h3 class="text-base text-on-dark">{{ $adv['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-on-dark-soft">{{ $adv['text'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($s['emdr']['steps'] as $step)
                <div class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} rounded-2xl border border-white/10 bg-white/5 p-6 shadow-lg transition-colors hover:bg-white/10">
                    <span class="font-serif text-3xl text-sky-300">{{ $step['n'] }}</span>
                    <h4 class="mt-2 text-base text-on-dark">{{ $step['title'] }}</h4>
                    <p class="mt-1 text-sm leading-relaxed text-on-dark-soft">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>

        @if ($hasAvailableCallSlots)
            <div
                class="mt-12"
                data-vue="ScheduleCallModal"
                data-props="{{ json_encode($scheduleCallProps + ['triggerLabel' => 'Reserva tu llamada gratuita', 'triggerClass' => 'btn bg-on-dark text-ink-panel hover:opacity-90'], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
            ></div>
        @endif
    </div>
</section>
@endif

{{-- ============ TESTIMONIOS (mosaico de tarjetas) ============ --}}
@if ($isSectionVisible('testimonials'))
<section id="testimonios" class="section bg-paper-50">
    <div class="container-x">
        <div class="max-w-2xl">
            <p class="eyebrow">Testimonios</p>
            <h2 class="mt-3 text-3xl sm:text-4xl">{{ $s['testimonials']['title'] }}</h2>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (array_merge($s['testimonials']['items'], ($patientTestimonials ?? collect())->toArray()) as $t)
                @php $rating = $t['rating'] ?? 5; @endphp
                <figure class="reveal reveal-delay-{{ ($loop->index % 6) + 1 }} flex flex-col rounded-3xl border border-paper-200 bg-card-fixed p-7 shadow-lg">
                    <div class="mb-4 flex gap-1 text-clay-400">
                        @for ($i = 0; $i < $rating; $i++)
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.9 7.5.6-5.7 5 1.8 7.4L12 17.8 5.4 21.9 7.2 14.5 1.5 9.5 9 8.9z"/></svg>
                        @endfor
                    </div>
                    <blockquote class="grow font-serif text-lg leading-relaxed text-on-card-fixed">
                        &ldquo;{{ $t['text'] }}&rdquo;
                    </blockquote>
                    <figcaption class="mt-5 text-sm font-bold text-on-card-fixed-soft">
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
    <div class="container-x">
        <div class="mx-auto max-w-4xl rounded-3xl border border-paper-200 bg-card-fixed p-8 shadow-lg sm:p-12">
            <div class="grid gap-10 md:grid-cols-[0.85fr_1.15fr] md:items-start">
                <div>
                    <p class="eyebrow">Herramienta gratuita</p>
                    <h2 class="mt-3 text-3xl !text-on-card-fixed sm:text-4xl">{{ $s['checkup']['title'] }}</h2>
                    <p class="mt-4 leading-relaxed text-on-card-fixed-soft">{{ $s['checkup']['subtitle'] }}</p>
                </div>
                <div data-vue="EmotionalCheckup" data-props="{{ json_encode($checkupProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
            </div>
        </div>
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
    <div class="container-x">
        <div class="mx-auto max-w-4xl rounded-3xl border border-paper-200 bg-card-fixed p-8 shadow-lg sm:p-12">
            <div class="grid gap-10 md:grid-cols-[0.85fr_1.15fr] md:items-start">
                <div>
                    <p class="eyebrow">Contacto</p>
                    <h2 class="mt-3 text-3xl !text-on-card-fixed sm:text-4xl">{{ $s['contact_section']['title'] }}</h2>
                    <p class="mt-4 leading-relaxed text-on-card-fixed-soft">{{ $s['contact_section']['subtitle'] }}</p>

                    <div class="mt-8 space-y-3">
                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 transition-colors hover:bg-white/15">
                            <span class="grid size-10 place-items-center rounded-full bg-white/10 text-sky-300">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.5A10 10 0 1012 2zm0 18a8 8 0 01-4.1-1.1l-.3-.2-2.8.9.9-2.7-.2-.3A8 8 0 1112 20zm4.4-5.6c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.5 6.5 0 01-1.9-1.2 7.2 7.2 0 01-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.5.3-.5v-.5c0-.1-.6-1.5-.8-2s-.4-.4-.6-.4h-.5a1 1 0 00-.7.3A2.9 2.9 0 006 6.6c0 1.7 1.3 3.4 1.5 3.6s2.5 3.9 6.1 5.3c2.2.8 2.7.7 3.2.6s1.4-.6 1.6-1.1a2 2 0 00.1-1.1c0-.2-.2-.3-.4-.4z"/></svg>
                            </span>
                            <span>
                                <span class="block text-xs font-bold uppercase tracking-wider text-on-card-fixed-soft">WhatsApp</span>
                                <span class="block text-sm font-semibold text-on-card-fixed">{{ $s['contact']['whatsapp_show'] }}</span>
                            </span>
                        </a>
                        @if (! empty($telegramUsername))
                            <a href="https://t.me/{{ $telegramUsername }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 transition-colors hover:bg-white/15">
                                <span class="grid size-10 place-items-center rounded-full bg-white/10 text-sky-300">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.6 20c-.2 1-.9 1.3-1.7.8l-4.7-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9L18 7.5c.4-.3-.1-.5-.6-.2L6.6 14.2l-4.8-1.5c-1-.3-1-1 .2-1.5L20.6 3.4c.8-.3 1.6.2 1.3 1z"/></svg>
                                </span>
                                <span>
                                    <span class="block text-xs font-bold uppercase tracking-wider text-on-card-fixed-soft">Telegram</span>
                                    <span class="block text-sm font-semibold text-on-card-fixed">{{ '@'.$telegramUsername }}</span>
                                </span>
                            </a>
                        @endif
                        <a href="mailto:{{ $s['contact']['email'] }}" class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 transition-colors hover:bg-white/15">
                            <span class="grid size-10 place-items-center rounded-full bg-white/10 text-sky-300">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            </span>
                            <span>
                                <span class="block text-xs font-bold uppercase tracking-wider text-on-card-fixed-soft">Email</span>
                                <span class="block text-sm font-semibold text-on-card-fixed">{{ $s['contact']['email'] }}</span>
                            </span>
                        </a>
                        <div class="rounded-2xl bg-white/10 p-4 text-sm text-on-card-fixed-soft">
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
        </div>
    </div>
</section>
@endif

@endsection
