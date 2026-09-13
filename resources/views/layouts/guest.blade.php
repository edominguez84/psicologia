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
    @endphp
    <link rel="icon" href="{{ ! empty($favicon['path']) ? \Illuminate\Support\Facades\Storage::url($favicon['path']) : asset('favicon.ico') }}">
    @include('partials.google-fonts-link', ['fonts' => $fonts])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center bg-sky-50 px-4 py-10">
        <a href="/" class="mb-6">
            @include('partials.logo')
        </a>

        <div class="w-full max-w-sm rounded-3xl border border-paper-200 bg-white p-6 shadow-sm sm:p-8">
            {{ $slot }}
        </div>

        <a href="/" class="mt-6 text-sm font-semibold text-sky-700 hover:underline">← Volver al sitio</a>
    </div>
</body>
</html>
