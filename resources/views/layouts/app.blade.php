<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('site.name').' · '.config('site.role'))</title>
    <meta name="description" content="@yield('meta_description', 'Terapia psicológica online en español, especializada en trauma y EMDR. Sesiones por videollamada para personas en Estados Unidos y Europa.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php $colors = app(\App\Services\SiteSettingsService::class)->get('colors'); @endphp
    @if ($colors)
        <style>
            :root {
                @if (!empty($colors['primary']))
                    --color-sky-600: {{ $colors['primary'] }};
                    --color-sky-700: {{ \App\Support\ColorHelper::darken($colors['primary'], 0.15) }};
                @endif
                @if (!empty($colors['background']))
                    --color-paper-50: {{ $colors['background'] }};
                @endif
                @if (!empty($colors['accent']))
                    --color-clay-400: {{ $colors['accent'] }};
                    --color-clay-500: {{ \App\Support\ColorHelper::darken($colors['accent'], 0.1) }};
                @endif
                @if (!empty($colors['text']))
                    --color-ink: {{ $colors['text'] }};
                @endif
            }
        </style>
    @endif
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
        $chatbotAboutPhoto = app(\App\Services\SiteSettingsService::class)->get('about_photo');
        $chatbotAvatar = ! empty($chatbotAboutPhoto['path'])
            ? \Illuminate\Support\Facades\Storage::url($chatbotAboutPhoto['path'])
            : asset(config('site.about.photo'));
        $chatbotNameParts = explode(' ', (string) config('site.name'));
        $chatbotOwnerFirstName = $chatbotNameParts[1] ?? ($chatbotNameParts[0] ?? config('site.name'));
        $chatbotProps = [
            'botName'    => 'Rebecca',
            'botTagline' => 'Asistente virtual de '.$chatbotOwnerFirstName,
            'avatar'     => $chatbotAvatar,
            'endpoint'   => $chatbotDemoMode ? null : route('chatbot-lead.store'),
            'demoMode'   => $chatbotDemoMode,
            'whatsapp'   => $chatbotWhatsapp,
        ];
    @endphp
    <div data-vue="ChatbotWidget" data-props="{{ json_encode($chatbotProps, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
</body>
</html>
