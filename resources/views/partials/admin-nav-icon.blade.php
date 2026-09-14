@props(['name' => 'circle'])

@php
    // Set de iconos de línea simple para el menú del panel admin. Mismo
    // estilo de trazo (stroke, sin relleno) que partials/icon.blade.php,
    // pero con un vocabulario de iconos de navegación en vez de temáticos.
    $paths = [
        'home'        => '<path d="M4 11.5 12 4l8 7.5M6 10v9a1 1 0 001 1h4v-6h2v6h4a1 1 0 001-1v-9"/>',
        'palette'     => '<circle cx="12" cy="12" r="9"/><circle cx="8.5" cy="10.5" r="1.2" fill="currentColor" stroke="none"/><circle cx="12" cy="8" r="1.2" fill="currentColor" stroke="none"/><circle cx="15.5" cy="10.5" r="1.2" fill="currentColor" stroke="none"/><path d="M12 21a9 9 0 010-18c1 3 3 2 3 4.5S13 12 16 12c2 0 3.5 1 3.5 3-1 4-4.5 6-7.5 6z"/>',
        'eye'         => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'layout'      => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/>',
        'image'       => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m3 17 5-5 4 4 4-4 5 5"/>',
        'photo'       => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M8 6l1.5-2h5L16 6"/><circle cx="12" cy="13" r="3.5"/>',
        'grid'        => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'share'       => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="M8.2 10.8l7.6-4.6M8.2 13.2l7.6 4.6"/>',
        'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'inbox'       => '<path d="M4 12h4l1.5 3h5L16 12h4M4 12l1.5-6.5A1 1 0 016.5 4h11a1 1 0 011 1.5L20 12M4 12v6a1 1 0 001 1h14a1 1 0 001-1v-6"/>',
        'star'        => '<path d="M12 3l2.6 5.6 6.1.6-4.6 4.1 1.3 6-5.4-3.1L6.6 19.3l1.3-6-4.6-4.1 6.1-.6z"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'calendar'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'people'      => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0112 0M16 6a3 3 0 010 6M15 20a6 6 0 016-6"/>',
        'lock'        => '<rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>',
        'user-plus'   => '<circle cx="9" cy="8" r="3"/><path d="M2.5 20a6.5 6.5 0 0113 0"/><path d="M18 8v6M15 11h6"/>',
        'chat'        => '<path d="M21 11.5a8.4 8.4 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.4 8.4 0 01-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.4 8.4 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>',
        'shield-lock' => '<path d="M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3z"/><path d="M10 12.5V11a2 2 0 114 0v1.5M9.3 12.5h5.4a.8.8 0 01.8.8v2a.8.8 0 01-.8.8H9.3a.8.8 0 01-.8-.8v-2a.8.8 0 01.8-.8z"/>',
        'browser'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 8h18"/><circle cx="6" cy="6" r=".6" fill="currentColor" stroke="none"/><circle cx="8.2" cy="6" r=".6" fill="currentColor" stroke="none"/>',
        'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14 0 18M12 3c-3 3.5-3 14 0 18"/>',
        'document'    => '<path d="M7 3h7l4 4v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"/><path d="M14 3v4h4M9 12h6M9 15.5h6M9 8.5h2"/>',
        'clipboard'   => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3a1 1 0 011-1h4a1 1 0 011 1v1"/><path d="M9 10h6M9 13.5h6M9 17h4"/>',
        'terminal'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="m7 9 3 3-3 3M13 15h4"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-4', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }} viewBox="0 0 24 24">
    {!! $paths[$name] ?? $paths['circle'] ?? '<circle cx="12" cy="12" r="8"/>' !!}
</svg>
