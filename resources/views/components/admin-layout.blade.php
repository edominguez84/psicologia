@props(['title' => 'Panel'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} · Admin · {{ config('site.name') }}</title>

    @php $fonts = app(\App\Services\SiteSettingsService::class)->get('fonts', \App\Support\FontOptions::defaults()); @endphp
    @include('partials.google-fonts-link', ['fonts' => $fonts])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-sky-50">
    <div class="min-h-screen md:flex">
        {{-- Navegación lateral (móvil: barra superior + menú colapsable con Alpine) --}}
        <div x-data="{ open: false }" class="md:contents">
            <header class="flex items-center justify-between border-b border-paper-200 bg-white px-4 py-3 md:hidden">
                <a href="{{ route('admin.dashboard') }}">@include('partials.logo', ['class' => 'h-8 w-auto'])</a>
                <button type="button" @click="open = !open" class="grid size-10 place-items-center rounded-full border border-paper-200 text-sky-700" aria-label="Abrir menú">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M2 4h14M2 9h14M2 14h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                </button>
            </header>

            <aside
                x-show="open"
                x-transition
                class="w-full shrink-0 border-b border-paper-200 bg-white px-4 py-6 md:!block md:w-64 md:border-b-0 md:border-r md:px-6 md:py-8"
            >
                <a href="{{ route('admin.dashboard') }}" class="mb-8 hidden md:block">@include('partials.logo')</a>

                <nav class="flex flex-col gap-1">
                    @php
                        $links = [
                            ['route' => 'admin.dashboard', 'label' => 'Panel'],
                            ['route' => 'admin.theme.edit', 'label' => 'Apariencia'],
                            ['route' => 'admin.section-visibility.edit', 'label' => 'Visibilidad de secciones'],
                            ['route' => 'admin.custom-sections.index', 'label' => 'Secciones personalizadas'],
                            ['route' => 'admin.logo.edit', 'label' => 'Logo'],
                            ['route' => 'admin.about-photo.edit', 'label' => 'Foto de portada'],
                            ['route' => 'admin.gallery.edit', 'label' => 'Galería'],
                            ['route' => 'admin.social.edit', 'label' => 'Redes sociales'],
                            ['route' => 'admin.contact.edit', 'label' => 'Contacto'],
                            ['route' => 'admin.messages.index', 'label' => 'Mensajes'],
                            ['route' => 'admin.testimonials.index', 'label' => 'Testimonios'],
                            ['route' => 'admin.appointment-slots.index', 'label' => 'Horarios de citas'],
                            ['route' => 'admin.appointments.index', 'label' => 'Citas'],
                            ['route' => 'admin.users.index', 'label' => 'Usuarios'],
                            ['route' => 'admin.security.edit', 'label' => 'Seguridad'],
                            ['route' => 'two-factor.edit', 'label' => 'Mi seguridad'],
                        ];
                        if (auth()->user()?->isSuperAdmin()) {
                            $links[] = ['route' => 'admin.chatbot-faqs.index', 'label' => 'Preguntas del chatbot'];
                            $links[] = ['route' => 'admin.staff.create', 'label' => 'Crear cuenta'];
                        }
                    @endphp
                    @foreach ($links as $link)
                        <a
                            href="{{ route($link['route']) }}"
                            class="rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs($link['route']) ? 'bg-sky-100 text-sky-800' : 'text-ink-soft hover:bg-sky-50' }}"
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    <div class="my-3 border-t border-paper-200"></div>

                    <p class="px-3 text-xs font-bold uppercase tracking-wider text-sky-500">Contenido</p>
                    @foreach (\App\Support\SiteContentSections::all() as $key => $section)
                        <a
                            href="{{ route('admin.content.edit', $key) }}"
                            class="rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.content.edit') && request()->route('section') === $key ? 'bg-sky-100 text-sky-800' : 'text-ink-soft hover:bg-sky-50' }}"
                        >
                            {{ $section['label'] }}
                        </a>
                    @endforeach

                    <div class="my-3 border-t border-paper-200"></div>

                    <a href="/" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-ink-soft hover:bg-sky-50">← Ver el sitio</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-clay-500 hover:bg-paper-100">
                            Cerrar sesión
                        </button>
                    </form>
                </nav>
            </aside>
        </div>

        <main class="min-w-0 flex-1 px-4 py-8 sm:px-6 md:px-10 md:py-10">
            <div class="mx-auto max-w-4xl">
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
