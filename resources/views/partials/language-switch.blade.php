{{--
    Switch de idioma del contenido de fábrica de la landing (ver
    App\Http\Middleware\ResolveSiteLocale). Dos links simples, sin JS —
    cada uno guarda la cookie y redirige de vuelta a la misma página.
--}}
@php
    $currentLocale = request()->attributes->get('site_locale', 'es');
@endphp
<div class="flex items-center gap-0.5 rounded-full border border-paper-200 bg-paper-50 p-1 text-xs font-bold">
    <a
        href="{{ route('site-locale.switch', 'es') }}"
        class="rounded-full px-2.5 py-1 transition-colors {{ $currentLocale === 'es' ? 'bg-sky-600 text-on-dark' : 'text-ink-soft hover:text-sky-700' }}"
    >ES</a>
    <a
        href="{{ route('site-locale.switch', 'en') }}"
        class="rounded-full px-2.5 py-1 transition-colors {{ $currentLocale === 'en' ? 'bg-sky-600 text-on-dark' : 'text-ink-soft hover:text-sky-700' }}"
    >EN</a>
</div>
