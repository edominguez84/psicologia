@props(['dark' => false])

@php
    // Enlaces de redes sociales: se dejan listos con "#" hasta que se
    // confirmen las cuentas reales. Cambia el href aquí cuando existan.
    $networks = [
        ['name' => 'Facebook', 'href' => '#', 'icon' => 'facebook'],
        ['name' => 'Instagram', 'href' => '#', 'icon' => 'instagram'],
        ['name' => 'TikTok', 'href' => '#', 'icon' => 'tiktok'],
        ['name' => 'LinkedIn', 'href' => '#', 'icon' => 'linkedin'],
    ];

    $paths = [
        'facebook' => '<path d="M13.5 9H16V6h-2.5C11.6 6 10 7.6 10 9.9V12H8v3h2v7h3v-7h2.4l.6-3H13v-1.7c0-.8.2-1.3 1.5-1.3Z" fill="currentColor" stroke="none"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1" fill="currentColor" stroke="none"/>',
        'tiktok' => '<path d="M14 4v10.2a2.8 2.8 0 1 1-2-2.7"/><path d="M14 4c.3 2 1.8 3.6 4 4"/>',
        'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M8 10.5v6M8 7.8v.01M12 16.5v-3.7c0-1.2.9-2.1 2-2.1s2 .9 2 2.1v3.7M12 16.5v-6"/>',
    ];

    $circleClasses = $dark
        ? 'border border-white/15 bg-white/5 text-paper-50 hover:bg-white/10'
        : 'border border-paper-200 bg-white text-sky-700 hover:border-sky-300 hover:bg-sky-50';
@endphp

<div class="flex items-center gap-2.5">
    @foreach ($networks as $network)
        <a
            href="{{ $network['href'] }}"
            aria-label="{{ $network['name'] }}"
            class="grid size-9 shrink-0 place-items-center rounded-full transition-colors {{ $circleClasses }}"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                {!! $paths[$network['icon']] !!}
            </svg>
        </a>
    @endforeach
</div>
