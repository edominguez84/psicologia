{{--
    Construye el <link> de Google Fonts según la tipografía elegida en
    /admin/theme (site_settings.fonts). Se usa tanto en el sitio público
    (layouts/app.blade.php) como en el panel admin, para que ambos reflejen
    la misma elección. Si la key guardada ya no existe en el catálogo
    (App\Support\FontOptions), se cae al default sin romper el <head>.
--}}
@php
    $googleFontsHeadingKey = $fonts['heading'] ?? \App\Support\FontOptions::defaults()['heading'];
    $googleFontsBodyKey = $fonts['body'] ?? \App\Support\FontOptions::defaults()['body'];

    $googleFontsHeading = \App\Support\FontOptions::findHeading($googleFontsHeadingKey)
        ?? \App\Support\FontOptions::findHeading(\App\Support\FontOptions::defaults()['heading']);
    $googleFontsBody = \App\Support\FontOptions::findBody($googleFontsBodyKey)
        ?? \App\Support\FontOptions::findBody(\App\Support\FontOptions::defaults()['body']);

    $googleFontsUrl = 'https://fonts.googleapis.com/css2?family='
        .$googleFontsHeading['google'].'&family='.$googleFontsBody['google'].'&display=swap';
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ $googleFontsUrl }}" rel="stylesheet">
