@php
    $checked = $checked ?? [];
    $historicFeatures = collect($features)->filter(fn ($f) => $f['default'] === true);
    $delegableFeatures = collect($features)->filter(fn ($f) => $f['default'] === false);
@endphp

<p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-soft">Secciones generales del panel</p>
<div class="grid gap-2 sm:grid-cols-2">
    @foreach ($historicFeatures as $key => $feature)
        <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm">
            <input
                type="checkbox"
                name="features[]"
                value="{{ $key }}"
                @checked(in_array($key, $checked, true))
                class="accent-sky-600"
            >
            {{ $feature['label'] }}
        </label>
    @endforeach
</div>

<p class="mb-2 mt-6 text-xs font-semibold uppercase tracking-wide text-ink-soft">
    Secciones reservadas a super administrador (delegar solo si es necesario)
</p>
<div class="grid gap-2 sm:grid-cols-2">
    @foreach ($delegableFeatures as $key => $feature)
        <label class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm">
            <input
                type="checkbox"
                name="features[]"
                value="{{ $key }}"
                @checked(in_array($key, $checked, true))
                class="accent-amber-600"
            >
            {{ $feature['label'] }}
        </label>
    @endforeach
</div>
