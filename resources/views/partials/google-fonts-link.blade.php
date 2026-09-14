{{--
    Construye el <link> de Google Fonts según la tipografía elegida en
    /admin/theme (site_settings.fonts). Se usa tanto en el sitio público
    (layouts/app.blade.php) como en el panel admin, para que ambos reflejen
    la misma elección. Si la key guardada ya no existe en el catálogo
    (App\Support\FontOptions), se cae al default sin romper el <head>.
--}}
@php
    $googleFontsDefaults = \App\Support\FontOptions::defaults();
    $googleFontsHeadingKey = $fonts['heading'] ?? $googleFontsDefaults['heading'];
    $googleFontsBodyKey = $fonts['body'] ?? $googleFontsDefaults['body'];
    $googleFontsButtonKey = $fonts['button'] ?? $googleFontsDefaults['button'];

    $googleFontsHeading = \App\Support\FontOptions::findHeading($googleFontsHeadingKey)
        ?? \App\Support\FontOptions::findHeading($googleFontsDefaults['heading']);
    $googleFontsBody = \App\Support\FontOptions::findBody($googleFontsBodyKey)
        ?? \App\Support\FontOptions::findBody($googleFontsDefaults['body']);
    $googleFontsButton = \App\Support\FontOptions::findBody($googleFontsButtonKey)
        ?? \App\Support\FontOptions::findBody($googleFontsDefaults['button']);

    // Familias únicas (evita pedir dos veces la misma fuente si botones usa
    // la misma tipografía que el texto general, el caso más común).
    $googleFontsFamilies = collect([$googleFontsHeading['google'], $googleFontsBody['google'], $googleFontsButton['google']])
        ->unique()
        ->implode('&family=');

    $googleFontsUrl = 'https://fonts.googleapis.com/css2?family='.$googleFontsFamilies.'&display=swap';
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ $googleFontsUrl }}" rel="stylesheet">
