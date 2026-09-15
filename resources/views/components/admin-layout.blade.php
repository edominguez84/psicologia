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
            <header class="flex items-center justify-between gap-2 border-b border-paper-200 bg-paper-alt px-4 py-3 md:hidden">
                <a href="{{ route('admin.dashboard') }}" class="min-w-0 shrink">@include('partials.logo', ['class' => 'h-8 w-auto'])</a>
                <div class="flex shrink-0 items-center gap-2">
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
                <div class="mb-8 hidden items-center justify-between gap-2 md:flex">
                    <a href="{{ route('admin.dashboard') }}" class="min-w-0 shrink">@include('partials.logo')</a>
                    <div class="shrink-0">
                        @include('partials.theme-switch')
                    </div>
                </div>

                <nav class="flex flex-col gap-1">
                    @php
                        // 'Panel' vive suelto arriba (es el destino por defecto,
                        // no una categoría). El resto se agrupa por tipo de
                        // tarea para que el menú no sea una lista plana de 20+
                        // opciones — cada grupo se puede colapsar (Alpine,
                        // estado recordado en localStorage por grupo).
                        $groups = [
                            'apariencia' => [
                                'label' => 'Apariencia y contenido',
                                'links' => [
                                    ['route' => 'admin.theme.edit', 'label' => 'Apariencia', 'icon' => 'palette'],
                                    ['route' => 'admin.section-visibility.edit', 'label' => 'Visibilidad de secciones', 'icon' => 'eye'],
                                    ['route' => 'admin.custom-sections.index', 'label' => 'Secciones personalizadas', 'icon' => 'layout'],
                                    ['route' => 'admin.logo.edit', 'label' => 'Logo', 'icon' => 'image'],
                                    ['route' => 'admin.about-photo.edit', 'label' => 'Foto de portada', 'icon' => 'photo'],
                                    ['route' => 'admin.gallery.edit', 'label' => 'Galería', 'icon' => 'grid'],
                                    ['route' => 'admin.social.edit', 'label' => 'Redes sociales', 'icon' => 'share'],
                                    ['route' => 'admin.contact.edit', 'label' => 'Contacto', 'icon' => 'mail'],
                                    ['route' => 'admin.contact-form.edit', 'label' => 'Formulario de contacto', 'icon' => 'inbox'],
                                ],
                            ],
                            'operacion' => [
                                'label' => 'Operación',
                                'links' => [
                                    ['route' => 'admin.messages.index', 'label' => 'Mensajes', 'icon' => 'inbox'],
                                    ['route' => 'admin.appointment-slots.index', 'label' => 'Horarios de citas', 'icon' => 'clock'],
                                    ['route' => 'admin.appointments.index', 'label' => 'Citas', 'icon' => 'calendar'],
                                ],
                            ],
                            'sistema' => [
                                'label' => 'Sistema y seguridad',
                                'links' => [
                                    ['route' => 'admin.security.edit', 'label' => 'Seguridad', 'icon' => 'lock'],
                                    ['route' => 'two-factor.edit', 'label' => 'Mi seguridad', 'icon' => 'shield-lock'],
                                ],
                            ],
                        ];

                        if (auth()->user()?->isSuperAdmin()) {
                            $groups['apariencia']['links'][] = ['route' => 'admin.promotions.index', 'label' => 'Promociones y planes', 'icon' => 'card'];
                            $groups['apariencia']['links'][] = ['route' => 'admin.chatbot-faqs.index', 'label' => 'Preguntas del chatbot', 'icon' => 'chat'];
                            $groups['apariencia']['links'][] = ['route' => 'admin.favicon.edit', 'label' => 'Icono del sitio', 'icon' => 'browser'];
                            $groups['apariencia']['links'][] = ['route' => 'admin.legal.edit', 'params' => ['page' => 'privacy'], 'label' => 'Páginas legales', 'icon' => 'document'];

                            $groups['operacion']['links'][] = ['route' => 'admin.testimonials.index', 'label' => 'Testimonios', 'icon' => 'star'];

                            $groups['sistema']['links'][] = ['route' => 'admin.users.index', 'label' => 'Usuarios', 'icon' => 'people'];
                            $groups['sistema']['links'][] = ['route' => 'admin.staff.create', 'label' => 'Crear cuenta', 'icon' => 'user-plus'];
                            $groups['sistema']['links'][] = ['route' => 'admin.activity-log.index', 'label' => 'Registro de auditoría', 'icon' => 'clipboard'];
                            $groups['sistema']['links'][] = ['route' => 'admin.system-log.index', 'label' => 'Logs del sistema', 'icon' => 'terminal'];
                            $groups['sistema']['links'][] = ['route' => 'admin.payment-settings.edit', 'label' => 'Métodos de pago', 'icon' => 'card'];
                            $groups['sistema']['links'][] = ['route' => 'admin.profanity-filter.edit', 'label' => 'Filtro de contenido', 'icon' => 'shield-lock'];
                            $groups['sistema']['links'][] = ['route' => 'admin.reports.index', 'label' => 'Informe del sistema', 'icon' => 'document'];
                        }

                        // Si la página activa está dentro de un grupo, ese grupo
                        // empieza expandido aunque localStorage diga lo
                        // contrario — nunca se abre el panel "escondiendo" la
                        // sección en la que ya se está.
                        $activeGroup = null;
                        foreach ($groups as $key => $group) {
                            foreach ($group['links'] as $link) {
                                if (request()->routeIs($link['route'])) {
                                    $activeGroup = $key;
                                }
                            }
                        }
                    @endphp

                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-sky-100 text-sky-800' : 'text-ink-soft hover:bg-sky-50' }}"
                    >
                        @include('partials.admin-nav-icon', ['name' => 'home'])
                        Panel
                    </a>

                    <div class="my-2 border-t border-paper-200"></div>

                    @foreach ($groups as $key => $group)
                        <div x-data="{ open: {{ $activeGroup === $key ? 'true' : "(localStorage.getItem('adminNavGroup:{$key}') ?? 'false') === 'true'" }} }" x-init="$watch('open', value => localStorage.setItem('adminNavGroup:{$key}', value))">
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-xs font-bold uppercase tracking-wider text-sky-500 hover:bg-sky-50"
                            >
                                {{ $group['label'] }}
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" class="shrink-0 transition-transform" :class="open ? 'rotate-180' : ''">
                                    <path d="M2.5 4.5L6 8l3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition>
                                @foreach ($group['links'] as $link)
                                    <a
                                        href="{{ route($link['route'], $link['params'] ?? []) }}"
                                        class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs($link['route']) ? 'bg-sky-100 text-sky-800' : 'text-ink-soft hover:bg-sky-50' }}"
                                    >
                                        @include('partials.admin-nav-icon', ['name' => $link['icon']])
                                        {{ $link['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="my-2 border-t border-paper-200"></div>

                    @php
                        $contentActive = request()->routeIs('admin.content.edit');
                    @endphp
                    <div x-data="{ open: {{ $contentActive ? 'true' : "(localStorage.getItem('adminNavGroup:contenido') ?? 'false') === 'true'" }} }" x-init="$watch('open', value => localStorage.setItem('adminNavGroup:contenido', value))">
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-xs font-bold uppercase tracking-wider text-sky-500 hover:bg-sky-50"
                        >
                            Contenido
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" class="shrink-0 transition-transform" :class="open ? 'rotate-180' : ''">
                                <path d="M2.5 4.5L6 8l3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition>
                            @foreach (\App\Support\SiteContentSections::all() as $key => $section)
                                <a
                                    href="{{ route('admin.content.edit', $key) }}"
                                    class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.content.edit') && request()->route('section') === $key ? 'bg-sky-100 text-sky-800' : 'text-ink-soft hover:bg-sky-50' }}"
                                >
                                    @include('partials.admin-nav-icon', ['name' => 'layout'])
                                    {{ $section['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

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
