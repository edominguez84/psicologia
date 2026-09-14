@props(['title' => 'Mi cuenta'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} · {{ config('site.name') }}</title>

    @php
        $fonts = app(\App\Services\SiteSettingsService::class)->get('fonts', \App\Support\FontOptions::defaults());
        $favicon = app(\App\Services\SiteSettingsService::class)->get('favicon');
    @endphp
    <link rel="icon" href="{{ ! empty($favicon['path']) ? \Illuminate\Support\Facades\Storage::url($favicon['path']) : asset('favicon.ico') }}">
    @include('partials.google-fonts-link', ['fonts' => $fonts])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-sky-50">
    <div class="min-h-screen md:flex">
        {{-- Navegación lateral (móvil: barra superior + menú colapsable con Alpine) --}}
        <div x-data="{ open: false }" class="md:contents">
            <header class="flex items-center justify-between border-b border-paper-200 bg-white px-4 py-3 md:hidden">
                <a href="{{ route('patient.profile.edit') }}">@include('partials.logo', ['class' => 'h-8 w-auto'])</a>
                <button type="button" @click="open = !open" class="grid size-10 place-items-center rounded-full border border-paper-200 text-sky-700" aria-label="Abrir menú">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M2 4h14M2 9h14M2 14h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                </button>
            </header>

            <aside
                x-show="open"
                x-transition
                class="w-full shrink-0 border-b border-paper-200 bg-white px-4 py-6 md:!block md:w-64 md:border-b-0 md:border-r md:px-6 md:py-8"
            >
                <a href="{{ route('patient.profile.edit') }}" class="mb-8 hidden md:block">@include('partials.logo')</a>

                <nav class="flex flex-col gap-1">
                    @php
                        $links = [
                            ['route' => 'patient.profile.edit', 'label' => 'Mi perfil', 'icon' => 'home'],
                            ['route' => 'patient.appointments.index', 'label' => 'Mis citas', 'icon' => 'calendar'],
                            ['route' => 'patient.testimonial.edit', 'label' => 'Mi testimonio', 'icon' => 'star'],
                            ['route' => 'two-factor.edit', 'label' => 'Mi seguridad', 'icon' => 'shield-lock'],
                        ];
                    @endphp
                    @foreach ($links as $link)
                        <a
                            href="{{ route($link['route']) }}"
                            class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs($link['route']) ? 'bg-sky-100 text-sky-800' : 'text-ink-soft hover:bg-sky-50' }}"
                        >
                            @include('partials.admin-nav-icon', ['name' => $link['icon']])
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    <div class="my-3 border-t border-paper-200"></div>

                    <a href="/" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink-soft hover:bg-sky-50">
                        @include('partials.admin-nav-icon', ['name' => 'globe'])
                        Ver el sitio
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-clay-500 hover:bg-paper-100">
                            @include('partials.admin-nav-icon', ['name' => 'lock'])
                            Cerrar sesión
                        </button>
                    </form>
                </nav>
            </aside>
        </div>

        <main class="min-w-0 flex-1 px-4 py-8 sm:px-6 md:px-10 md:py-10">
            <div class="mx-auto max-w-4xl">
                @auth
                    <p class="mb-4 text-sm font-semibold text-ink-soft">Bienvenido/a, {{ Auth::user()->name }}</p>
                @endauth

                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
