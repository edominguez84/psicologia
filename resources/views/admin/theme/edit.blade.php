<x-admin-layout title="Apariencia">
    <h1 class="text-2xl font-serif text-sky-800">Apariencia del sitio</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Ajusta la paleta pastel y la tipografía del sitio público. Los cambios se aplican al
        instante, sin necesidad de recompilar nada.
    </p>

    <form method="POST" action="{{ route('admin.theme.update') }}" class="mt-8 max-w-lg space-y-8">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <h2 class="text-lg font-serif text-sky-800">Colores</h2>

            @php
                $fields = [
                    'primary' => 'Color principal (botones, enlaces destacados)',
                    'background' => 'Fondo general del sitio',
                    'accent' => 'Acento cálido (detalles, estrellas)',
                    'text' => 'Color del texto',
                ];
            @endphp

            @foreach ($fields as $key => $label)
                <div>
                    <label for="{{ $key }}" class="mb-1.5 block text-sm font-semibold text-sky-700">{{ $label }}</label>
                    <div class="flex items-center gap-3">
                        <input
                            type="color"
                            id="{{ $key }}_picker"
                            value="{{ old($key, $colors[$key] ?? '#ffffff') }}"
                            class="size-11 shrink-0 cursor-pointer rounded-lg border border-paper-200"
                            oninput="document.getElementById('{{ $key }}').value = this.value"
                        >
                        <input
                            type="text"
                            name="{{ $key }}"
                            id="{{ $key }}"
                            value="{{ old($key, $colors[$key] ?? '') }}"
                            pattern="^#[0-9a-fA-F]{6}$"
                            placeholder="#386a97"
                            class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 font-mono text-sm outline-none focus:border-sky-400"
                            oninput="document.getElementById('{{ $key }}_picker').value = this.value"
                        >
                    </div>
                    @error($key)
                        <p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="space-y-6 border-t border-paper-200 pt-6">
            <h2 class="text-lg font-serif text-sky-800">Tipografía</h2>
            <p class="text-sm text-ink-soft">
                Elige entre fuentes verificadas para que los títulos y el texto general se vean
                bien, incluyendo tildes y la letra ñ.
            </p>

            <div>
                <label for="font_heading" class="mb-1.5 block text-sm font-semibold text-sky-700">Tipografía de títulos</label>
                <select
                    name="font_heading"
                    id="font_heading"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                >
                    @foreach ($headingFonts as $key => $font)
                        <option
                            value="{{ $key }}"
                            style="font-family: {{ $font['family'] }};"
                            @selected(old('font_heading', $fonts['heading'] ?? 'fraunces') === $key)
                        >{{ $font['label'] }}</option>
                    @endforeach
                </select>
                @error('font_heading')
                    <p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="font_body" class="mb-1.5 block text-sm font-semibold text-sky-700">Tipografía de texto general</label>
                <select
                    name="font_body"
                    id="font_body"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                >
                    @foreach ($bodyFonts as $key => $font)
                        <option
                            value="{{ $key }}"
                            style="font-family: {{ $font['family'] }};"
                            @selected(old('font_body', $fonts['body'] ?? 'nunito-sans') === $key)
                        >{{ $font['label'] }}</option>
                    @endforeach
                </select>
                @error('font_body')
                    <p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar apariencia</button>
    </form>
</x-admin-layout>
