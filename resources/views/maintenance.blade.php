<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sitio en mantenimiento · {{ config('site.name') }}</title>

    @php $favicon = app(\App\Services\SiteSettingsService::class)->get('favicon'); @endphp
    <link rel="icon" href="{{ ! empty($favicon['path']) ? \Illuminate\Support\Facades\Storage::url($favicon['path']) : asset('favicon.ico') }}">

    @vite(['resources/css/app.css'])
</head>
<body class="antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-b from-paper-50 to-sky-50 px-4 py-16">
        <div class="section-glow -right-32 -top-32 size-96 bg-sky-100"></div>
        <div class="section-glow -bottom-40 -left-32 size-96 bg-paper-200"></div>

        <div class="relative w-full max-w-lg rounded-[2rem] border border-paper-200 bg-card-fixed p-10 text-center shadow-2xl">
            @include('partials.logo', ['class' => 'mx-auto h-10 w-auto'])

            <span class="mx-auto mt-6 grid size-16 place-items-center rounded-full bg-sky-100 text-sky-600">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a4 4 0 015.6 5.6l-1.4 1.4-5.6-5.6 1.4-1.4z"/>
                    <path d="M13.3 7.7L4 17v3h3l9.3-9.3-3-3z"/>
                </svg>
            </span>

            <h1 class="mt-6 text-2xl !text-on-card-fixed sm:text-3xl">Página en mantenimiento</h1>
            <p class="mt-4 leading-relaxed text-on-card-fixed-soft">
                Estamos haciendo mejoras. Pronto estaremos de nuevo — mientras tanto, puedes
                comunicarte por estos medios para resolver cualquier duda que tengas.
            </p>

            @php
                $wa = 'https://wa.me/'.config('site.contact.whatsapp').'?text='.rawurlencode(config('site.whatsapp_prefill'));
            @endphp
            <div class="mt-8 space-y-3 text-left">
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-2xl border border-paper-200 bg-paper-50 p-4 transition-colors hover:border-sky-300">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.5A10 10 0 1012 2zm0 18a8 8 0 01-4.1-1.1l-.3-.2-2.8.9.9-2.7-.2-.3A8 8 0 1112 20zm4.4-5.6c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.5 6.5 0 01-1.9-1.2 7.2 7.2 0 01-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.5.3-.5v-.5c0-.1-.6-1.5-.8-2s-.4-.4-.6-.4h-.5a1 1 0 00-.7.3A2.9 2.9 0 006 6.6c0 1.7 1.3 3.4 1.5 3.6s2.5 3.9 6.1 5.3c2.2.8 2.7.7 3.2.6s1.4-.6 1.6-1.1a2 2 0 00.1-1.1c0-.2-.2-.3-.4-.4z"/></svg>
                    </span>
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-ink-soft">WhatsApp</span>
                        <span class="block text-sm font-semibold text-ink">{{ config('site.contact.whatsapp_show') }}</span>
                    </span>
                </a>
                <a href="mailto:{{ config('site.contact.email') }}" class="flex items-center gap-3 rounded-2xl border border-paper-200 bg-paper-50 p-4 transition-colors hover:border-sky-300">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    </span>
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-ink-soft">Email</span>
                        <span class="block text-sm font-semibold text-ink">{{ config('site.contact.email') }}</span>
                    </span>
                </a>
            </div>

            <div class="mt-8 flex justify-center">
                @include('partials.social-icons', ['heading' => 'Síguenos'])
            </div>
        </div>
    </div>
</body>
</html>
