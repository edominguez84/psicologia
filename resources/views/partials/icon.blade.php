@props(['name' => 'heart'])

@php
    $paths = [
        'shield'    => '<path d="M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3z"/>',
        'wind'      => '<path d="M4 9h9a3 3 0 100-6M3 14h13a3 3 0 110 6M3 19h6"/>',
        'cloud-rain'=> '<path d="M8 19v2M12 19v3M16 19v2M7 15a5 5 0 010-10 6 6 0 0111 2 4 4 0 010 8H7z"/>',
        'heart'     => '<path d="M12 20s-7-4.5-9.5-9A5 5 0 0112 5a5 5 0 019.5 6c-2.5 4.5-9.5 9-9.5 9z"/>',
        'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14 0 18M12 3c-3 3.5-3 14 0 18"/>',
        'users'     => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0112 0M16 6a3 3 0 010 6M15 20a6 6 0 016-6"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-6', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }} viewBox="0 0 24 24">
    {!! $paths[$name] ?? $paths['heart'] !!}
</svg>
