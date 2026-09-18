<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Aplica el modo claro/oscuro ANTES del primer render para evitar el
         parpadeo (FOUC): un usuario que eligió "oscuro" no debe ver un
         flash blanco mientras carga el bundle de Vite. Ver theme-mode.js
         para la lógica completa (persistencia y reacción a cambios en vivo). --}}
    <script>
        (function () {
            try {
                var mode = localStorage.getItem('themeMode') || 'system';
                var isDark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', isDark);
            } catch (e) {}
        })();
    </script>

    <title>@yield('title', config('site.name').' · '.config('site.role'))</title>
    <meta name="description" content="@yield('meta_description', 'Terapia psicológica online en español, especializada en trauma y EMDR. Sesiones por videollamada para personas en Estados Unidos y Europa.')">

    @php $favicon = app(\App\Services\SiteSettingsService::class)->get('favicon'); @endphp
    <link rel="icon" href="{{ ! empty($favicon['path']) ? \Illuminate\Support\Facades\Storage::url($favicon['path']) : asset('favicon.ico') }}">

    @php
        $colors = app(\App\Services\SiteSettingsService::class)->get('colors');
        $fonts = app(\App\Services\SiteSettingsService::class)->get('fonts', \App\Support\FontOptions::defaults());
        $headingFont = \App\Support\FontOptions::findHeading($fonts['heading'] ?? '') ?? \App\Support\FontOptions::findHeading(\App\Support\FontOptions::defaults()['heading']);
        $bodyFont = \App\Support\FontOptions::findBody($fonts['body'] ?? '') ?? \App\Support\FontOptions::findBody(\App\Support\FontOptions::defaults()['body']);
        $buttonFont = \App\Support\FontOptions::findBody($fonts['button'] ?? '') ?? \App\Support\FontOptions::findBody(\App\Support\FontOptions::defaults()['button']);
    @endphp
    @include('partials.google-fonts-link', ['fonts' => $fonts])

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            @if ($colors && !empty($colors['primary']))
                --color-sky-600: {{ $colors['primary'] }};
                --color-sky-700: {{ \App\Support\ColorHelper::darken($colors['primary'], 0.15) }};
            @endif
            @if ($colors && !empty($colors['background']))
                --color-paper-50: {{ $colors['background'] }};
            @endif
            @if ($colors && !empty($colors['accent']))
                --color-clay-400: {{ $colors['accent'] }};
                --color-clay-500: {{ \App\Support\ColorHelper::darken($colors['accent'], 0.1) }};
            @endif
            @if ($colors && !empty($colors['text']))
                --color-ink: {{ $colors['text'] }};
            @endif
            --font-serif: {{ $headingFont['family'] }};
            --font-sans: {{ $bodyFont['family'] }};
            --font-button: {{ $buttonFont['family'] }};
        }
    </style>
</head>
<body class="antialiased">
    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @php
        $chatbotDemoMode = (bool) config('site.demo_mode');
        $chatbotWhatsapp = 'https://wa.me/'.config('site.contact.whatsapp').'?text='.rawurlencode(config('site.whatsapp_prefill'));
        $chatbotAiService = app(\App\Services\ChatbotAiService::class);
        $chatbotAiEnabled = $chatbotDemoMode ? false : $chatbotAiService->isUsable();
        // Nombre y saludo: mientras la IA está activa, ChatbotAiService::
        // presentation() (configurado en /admin/chatbot-channels) es la
        // única fuente de verdad — así el nombre que ve el paciente al abrir
        // el widget siempre coincide con el que la IA usa para presentarse.
        // Con la IA apagada, se usa el nombre configurado en
        // /admin/chatbot-faqs (histórico, solo aplica al modo FAQ).
        $chatbotSettings = app(\App\Services\SiteSettingsService::class)->get('chatbot', ['name' => 'Rebecca']);
        if ($chatbotAiEnabled) {
            $chatbotPresentation = $chatbotAiService->presentation();
            $chatbotName = $chatbotPresentation['name'];
            $chatbotGreeting = $chatbotPresentation['greeting'];
        } else {
            $chatbotName = $chatbotSettings['name'] ?? 'Rebecca';
            $chatbotGreeting = null;
        }
        // Si no se subió una imagen propia del bot, se usa la foto de "Sobre
        // mí" como respaldo (comportamiento anterior), nunca una imagen rota.
        $chatbotAvatar = ! empty($chatbotSettings['avatar_path'])
            ? \Illuminate\Support\Facades\Storage::url($chatbotSettings['avatar_path'])
            : null;
        if (! $chatbotAvatar) {
            $chatbotAboutPhoto = app(\App\Services\SiteSettingsService::class)->get('about_photo');
            $chatbotAvatar = ! empty($chatbotAboutPhoto['path'])
                ? \Illuminate\Support\Facades\Storage::url($chatbotAboutPhoto['path'])
                : asset(config('site.about.photo'));
        }
        $chatbotNameParts = explode(' ', (string) config('site.name'));
        $chatbotOwnerFirstName = $chatbotNameParts[1] ?? ($chatbotNameParts[0] ?? config('site.name'));
        $chatbotFaqs = $chatbotDemoMode
            ? []
            : \App\Models\ChatbotFaq::active()->ordered()->get(['id', 'question', 'answer'])->toArray();
        // Mostrar/ocultar el widget + timeout de inactividad y su mensaje de
        // despedida — configurables en /admin/chatbot-channels, aplican
        // tanto al modo FAQ como al modo IA (ver ChatbotWidget.vue).
        $chatbotWidgetSettings = app(\App\Services\SiteSettingsService::class)->get('chatbot_channels', [])['chat_widget'] ?? [];
        $chatbotWebWidgetEnabled = $chatbotDemoMode ? true : ($chatbotWidgetSettings['web_widget_enabled'] ?? true);
        $chatbotProps = [
            'botName'     => $chatbotName,
            'botTagline'  => 'Asistente virtual de '.$chatbotOwnerFirstName,
            'greeting'    => $chatbotGreeting,
            'avatar'      => $chatbotAvatar,
            'endpoint'    => $chatbotDemoMode ? null : route('chatbot-lead.store'),
            'chatEndpoint' => $chatbotDemoMode ? null : route('chatbot-message.store'),
            'demoMode'    => $chatbotDemoMode,
            'whatsapp'    => $chatbotWhatsapp,
            'faqs'        => $chatbotFaqs,
            // Si la IA está activa y configurada, el widget conversa en vivo
            // en vez del flujo fijo de nombre→correo→teléfono→FAQ por
            // botones (ver App\Services\ChatbotAiService::isUsable()).
            'aiEnabled'   => $chatbotAiEnabled,
            'inactivityTimeoutMinutes' => (int) ($chatbotWidgetSettings['inactivity_timeout_minutes'] ?? 5),
            'farewellMessage' => $chatbotWidgetSettings['farewell_message'] ?? 'Veo que no tienes otra consulta, buen día, adiós.',
        ];
    @endphp
    @if ($chatbotWebWidgetEnabled)
        <div data-vue="ChatbotWidget" data-props="{{ json_encode($chatbotProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
    @endif

    @auth
        @php
            $inactivityTimeout = app(\App\Services\SiteSettingsService::class)->get('security')['inactivity_timeout_minutes'] ?? 30;
        @endphp
        @include('partials.inactivity-modal', ['timeoutMinutes' => $inactivityTimeout])
    @endauth
</body>
</html>
