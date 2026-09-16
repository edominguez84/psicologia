@php
    $checked = $checked ?? [];
@endphp
<div class="grid gap-2 sm:grid-cols-2">
    @foreach ($features as $key => $feature)
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
