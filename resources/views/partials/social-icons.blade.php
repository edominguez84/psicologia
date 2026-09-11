@props(['dark' => false, 'heading' => null])

@php
    // Los links reales se editan en /admin/social (guardados en site_settings).
    // Si un campo está vacío o no configurado, ese icono simplemente no se muestra.
    $saved = app(\App\Services\SiteSettingsService::class)->get('social', []);

    $labels = [
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'linkedin'  => 'LinkedIn',
        'youtube'   => 'YouTube',
        'x'         => 'X (Twitter)',
    ];

    $networks = collect($labels)
        ->map(fn ($name, $key) => ['name' => $name, 'href' => $saved[$key] ?? null, 'icon' => $key])
        ->filter(fn ($network) => ! empty($network['href']))
        ->values();

    $paths = [
        'facebook' => '<path d="M13.5 9H16V6h-2.5C11.6 6 10 7.6 10 9.9V12H8v3h2v7h3v-7h2.4l.6-3H13v-1.7c0-.8.2-1.3 1.5-1.3Z" fill="currentColor" stroke="none"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1" fill="currentColor" stroke="none"/>',
        'tiktok' => '<path d="M14 4v10.2a2.8 2.8 0 1 1-2-2.7"/><path d="M14 4c.3 2 1.8 3.6 4 4"/>',
        'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M8 10.5v6M8 7.8v.01M12 16.5v-3.7c0-1.2.9-2.1 2-2.1s2 .9 2 2.1v3.7M12 16.5v-6"/>',
        'youtube' => '<rect x="2.5" y="6" width="19" height="12" rx="4"/><path d="M10.5 9.5v5l4.5-2.5-4.5-2.5Z" fill="currentColor" stroke="none"/>',
        'x' => '<path d="M5 4.5 19 19.5M19 4.5 5 19.5"/>',
    ];

    $circleClasses = $dark
        ? 'border border-white/15 bg-white/5 text-paper-50 hover:bg-white/10'
        : 'border border-paper-200 bg-white text-sky-700 hover:border-sky-300 hover:bg-sky-50';
@endphp

@if ($networks->isNotEmpty())
    <div>
        @if ($heading)
            <p class="mb-3 text-xs font-bold uppercase tracking-wider {{ $dark ? 'text-paper-50' : 'text-sky-500' }}">{{ $heading }}</p>
        @endif
        <div class="flex items-center gap-2.5">
            @foreach ($networks as $network)
                <a
                    href="{{ $network['href'] }}"
                    target="_blank"
                    rel="noopener"
                    aria-label="{{ $network['name'] }}"
                    class="grid size-9 shrink-0 place-items-center rounded-full transition-colors {{ $circleClasses }}"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        {!! $paths[$network['icon']] !!}
                    </svg>
                </a>
            @endforeach
        </div>
    </div>
@endif
