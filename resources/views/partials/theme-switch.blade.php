{{--
    Switch de modo claro / oscuro / sistema. Preferencia 100% del navegador
    (localStorage), no de la cuenta ni del sitio — ver resources/js/theme-mode.js.
    Se usa tanto en la landing pública (partials/header.blade.php) como en el
    panel de administración (components/admin-layout.blade.php).
--}}
<div
    x-data="{ mode: (window.getThemeMode ? window.getThemeMode() : 'system') }"
    x-init="$watch('mode', value => window.setThemeMode(value))"
    class="flex items-center gap-0.5 rounded-full border border-paper-200 bg-paper-50 p-1"
    role="radiogroup"
    aria-label="Modo de color"
>
    @foreach ([
        'light' => ['label' => 'Claro', 'icon' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>'],
        'dark' => ['label' => 'Oscuro', 'icon' => '<path d="M20 14.5A8.5 8.5 0 019.5 4a8.5 8.5 0 1010.5 10.5z"/>'],
        'system' => ['label' => 'Sistema', 'icon' => '<rect x="3" y="4" width="18" height="13" rx="1.5"/><path d="M8 20h8M12 17v3"/>'],
    ] as $value => $option)
        <button
            type="button"
            role="radio"
            :aria-checked="mode === '{{ $value }}'"
            @click="mode = '{{ $value }}'"
            :class="mode === '{{ $value }}' ? 'bg-sky-600 text-on-dark' : 'text-ink-soft hover:text-sky-700'"
            class="grid size-8 place-items-center rounded-full transition-colors"
            aria-label="{{ $option['label'] }}"
            title="{{ $option['label'] }}"
        >
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                {!! $option['icon'] !!}
            </svg>
        </button>
    @endforeach
</div>
