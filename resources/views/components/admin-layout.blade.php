@props(['title' => 'Panel'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Ver el mismo script en layouts/app.blade.php: aplica el modo
         claro/oscuro antes del primer render para evitar el parpadeo. --}}
    <script>
        (function () {
            try {
                var mode = localStorage.getItem('themeMode') || 'system';
                var isDark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', isDark);
            } catch (e) {}
        })();
    </script>

    <title>{{ $title }} · Admin · {{ config('site.name') }}</title>

    @php
        $fonts = app(\App\Services\SiteSettingsService::class)->get('fonts', \App\Support\FontOptions::defaults());
        $favicon = app(\App\Services\SiteSettingsService::class)->get('favicon');
    @endphp
    <link rel="icon" href="{{ ! empty($favicon['path']) ? \Illuminate\Support\Facades\Storage::url($favicon['path']) : asset('favicon.ico') }}">
    @include('partials.google-fonts-link', ['fonts' => $fonts])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-paper-50">
    <div class="min-h-screen md:flex">
        {{-- Navegación lateral (móvil: barra superior + menú colapsable con Alpine) --}}
        <div x-data="{ open: false }" class="md:contents">
            <header class="flex items-center justify-between border-b border-paper-200 bg-paper-alt px-4 py-3 md:hidden">
                <a href="{{ route('admin.dashboard') }}">@include('partials.logo', ['class' => 'h-8 w-auto'])</a>
                <div class="flex items-center gap-2">
                    @include('partials.theme-switch')
                    <button type="button" @click="open = !open" class="grid size-10 place-items-center rounded-full border border-paper-200 text-sky-700" aria-label="Abrir menú">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M2 4h14M2 9h14M2 14h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                    </button>
                </div>
            </header>

            <aside
                x-show="open"
                x-transition
                class="w-full shrink-0 border-b border-paper-200 bg-paper-alt px-4 py-6 md:!block md:w-64 md:border-b-0 md:border-r md:px-6 md:py-8"
            >
                <div class="mb-8 hidden items-center justify-between md:flex">
                    <a href="{{ route('admin.dashboard') }}">@include('partials.logo')</a>
                    @include('partials.theme-switch')
                </div>

                <nav class="flex flex-col gap-1">
                    @php
                        $links = [
                            ['route' => 'admin.dashboard', 'label' => 'Panel', 'icon' => 'home'],
                            ['route' => 'admin.theme.edit', 'label' => 'Apariencia', 'icon' => 'palette'],
                            ['route' => 'admin.section-visibility.edit', 'label' => 'Visibilidad de secciones', 'icon' => 'eye'],
                            ['route' => 'admin.custom-sections.index', 'label' => 'Secciones personalizadas', 'icon' => 'layout'],
                            ['route' => 'admin.promotions.index', 'label' => 'Promociones y planes', 'icon' => 'card'],
                            ['route' => 'admin.logo.edit', 'label' => 'Logo', 'icon' => 'image'],
                            ['route' => 'admin.about-photo.edit', 'label' => 'Foto de portada', 'icon' => 'photo'],
                            ['route' => 'admin.gallery.edit', 'label' => 'Galería', 'icon' => 'grid'],
                            ['route' => 'admin.social.edit', 'label' => 'Redes sociales', 'icon' => 'share'],
                            ['route' => 'admin.contact.edit', 'label' => 'Contacto', 'icon' => 'mail'],
                            ['route' => 'admin.contact-form.edit', 'label' => 'Formulario de contacto', 'icon' => 'inbox'],
                            ['route' => 'admin.messages.index', 'label' => 'Mensajes', 'icon' => 'inbox'],
                            ['route' => 'admin.testimonials.index', 'label' => 'Testimonios', 'icon' => 'star'],
                            ['route' => 'admin.appointment-slots.index', 'label' => 'Horarios de citas', 'icon' => 'clock'],
                            ['route' => 'admin.appointments.index', 'label' => 'Citas', 'icon' => 'calendar'],
                            ['route' => 'admin.users.index', 'label' => 'Usuarios', 'icon' => 'people'],
                            ['route' => 'admin.security.edit', 'label' => 'Seguridad', 'icon' => 'lock'],
                            ['route' => 'two-factor.edit', 'label' => 'Mi seguridad', 'icon' => 'shield-lock'],
                        ];
                        if (auth()->user()?->isSuperAdmin()) {
                            $links[] = ['route' => 'admin.chatbot-faqs.index', 'label' => 'Preguntas del chatbot', 'icon' => 'chat'];
                            $links[] = ['route' => 'admin.staff.create', 'label' => 'Crear cuenta', 'icon' => 'user-plus'];
                            $links[] = ['route' => 'admin.favicon.edit', 'label' => 'Icono del sitio', 'icon' => 'browser'];
                            $links[] = ['route' => 'admin.legal.edit', 'params' => ['page' => 'privacy'], 'label' => 'Páginas legales', 'icon' => 'document'];
                            $links[] = ['route' => 'admin.activity-log.index', 'label' => 'Registro de auditoría', 'icon' => 'clipboard'];
                            $links[] = ['route' => 'admin.system-log.index', 'label' => 'Logs del sistema', 'icon' => 'terminal'];
                            $links[] = ['route' => 'admin.payment-settings.edit', 'label' => 'Métodos de pago', 'icon' => 'card'];
                        }
                    @endphp
                    @foreach ($links as $link)
                        <a
                            href="{{ route($link['route'], $link['params'] ?? []) }}"
                            class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs($link['route']) ? 'bg-sky-100 text-sky-800' : 'text-ink-soft hover:bg-sky-50' }}"
                        >
                            @include('partials.admin-nav-icon', ['name' => $link['icon']])
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    <div class="my-3 border-t border-paper-200"></div>

                    <p class="px-3 text-xs font-bold uppercase tracking-wider text-sky-500">Contenido</p>
                    @foreach (\App\Support\SiteContentSections::all() as $key => $section)
                        <a
                            href="{{ route('admin.content.edit', $key) }}"
                            class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.content.edit') && request()->route('section') === $key ? 'bg-sky-100 text-sky-800' : 'text-ink-soft hover:bg-sky-50' }}"
                        >
                            @include('partials.admin-nav-icon', ['name' => 'layout'])
                            {{ $section['label'] }}
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

    @auth
        @php
            $inactivityTimeout = app(\App\Services\SiteSettingsService::class)->get('security')['inactivity_timeout_minutes'] ?? 30;
        @endphp
        @include('partials.inactivity-modal', ['timeoutMinutes' => $inactivityTimeout])
    @endauth
</body>
</html>
