{{--
    Plantilla "Carrusel" de login — layout propio (no usa <x-guest-layout>,
    que también sirve a registro/2FA/recuperar contraseña y no debe
    tocarse). Mismo formulario/campos/nombres exactos que la plantilla
    clásica (ver auth/login.blade.php): name="email"/"password"/"remember",
    action route('login'), @csrf, mismos componentes de Breeze — solo
    cambia la disposición visual, ninguna lógica de
    AuthenticatedSessionController/LoginRequest/2FA se toca.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Acceso administración · {{ config('site.name') }}</title>

    @php
        $fonts = app(\App\Services\SiteSettingsService::class)->get('fonts', \App\Support\FontOptions::defaults());
        $favicon = app(\App\Services\SiteSettingsService::class)->get('favicon');
        $loginCarouselOverride = app(\App\Services\SiteSettingsService::class)->get('login_carousel');
        $loginCarouselImages = collect($loginCarouselOverride['images'] ?? \App\Http\Controllers\Admin\LoginCarouselController::DEFAULT_IMAGES)
            ->map(fn ($img) => [
                'url' => str_starts_with($img['path'], 'login-carousel/')
                    ? \Illuminate\Support\Facades\Storage::url($img['path'])
                    : asset($img['path']),
                'alt' => $img['alt'] ?: config('site.name'),
            ])
            ->values()
            ->all();
    @endphp
    <link rel="icon" href="{{ ! empty($favicon['path']) ? \Illuminate\Support\Facades\Storage::url($favicon['path']) : asset('favicon.ico') }}">
    @include('partials.google-fonts-link', ['fonts' => $fonts])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="grid min-h-screen md:grid-cols-2">
        <div id="login-carousel-pane" class="relative hidden bg-ink-panel md:block">
            @if (!empty($loginCarouselImages))
                <div
                    class="absolute inset-0"
                    data-vue="Carousel"
                    data-props="{{ json_encode(['images' => $loginCarouselImages, 'aspectClass' => 'h-full', 'intervalMs' => 7000], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                ></div>
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink-panel/70 via-transparent to-transparent"></div>
                <style>
                    /* Carousel.vue está pensado para incrustarse en el flujo
                       normal con un aspect-ratio (ver aspectClass) — aquí en
                       cambio debe llenar toda la columna izquierda a pantalla
                       completa, así que se fuerza altura 100% en cada nivel
                       (el contenedor relative, el track con scroll y cada
                       slide) en vez de dejar que el aspect-ratio decida la
                       altura. */
                    #login-carousel-pane, #login-carousel-pane [data-vue],
                    #login-carousel-pane [data-vue] > div,
                    #login-carousel-pane [data-vue] > div > div {
                        height: 100%;
                    }
                </style>
            @endif
            <a href="/" class="absolute left-8 top-8 z-10">
                @include('partials.logo', ['dark' => true, 'class' => 'h-9 w-auto'])
            </a>
        </div>

        <div class="flex items-center justify-center px-6 py-12 sm:px-10">
            <div class="w-full max-w-sm">
                <a href="/" class="mb-8 flex justify-center md:hidden">
                    @include('partials.logo')
                </a>

                <h1 class="text-center text-2xl font-serif text-sky-800 md:text-left">Bienvenido/a de nuevo</h1>
                <p class="mt-2 text-center text-sm text-ink-soft md:text-left">Ingresa a tu cuenta para continuar.</p>

                <x-auth-session-status class="mt-6" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Contraseña" />
                        <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink-soft">
                        <input id="remember_me" type="checkbox" name="remember" class="rounded border-paper-200 accent-sky-600">
                        Recordarme
                    </label>

                    <div class="flex items-center justify-between gap-4">
                        @if (Route::has('password.request'))
                            <a class="text-sm font-semibold text-sky-700 underline" href="{{ route('password.request') }}">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif

                        <x-primary-button>Entrar</x-primary-button>
                    </div>
                </form>

                <p class="mt-6 text-center text-sm text-ink-soft">
                    ¿Eres paciente y no tienes cuenta?
                    <a class="font-semibold text-sky-700 underline" href="{{ route('register') }}">Regístrate</a>
                </p>

                @php $oauth = app(\App\Services\SecurityAvailability::class)->oauth(); @endphp
                @if (in_array(true, $oauth, true))
                    <div class="mt-6 border-t border-paper-200 pt-6">
                        <p class="mb-3 text-center text-xs font-semibold uppercase tracking-wider text-ink-soft">O entra con</p>
                        <div class="flex flex-col gap-2">
                            @foreach (['google' => 'Google', 'facebook' => 'Facebook', 'microsoft' => 'Microsoft'] as $provider => $label)
                                @if ($oauth[$provider])
                                    <a href="{{ route('oauth.redirect', $provider) }}" class="btn btn-ghost w-full">
                                        {{ $label }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <a href="/" class="mt-8 block text-center text-sm font-semibold text-sky-700 hover:underline">← Volver al sitio</a>
            </div>
        </div>
    </div>
</body>
</html>
