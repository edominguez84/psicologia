<x-admin-layout title="Colores">
    <h1 class="text-2xl font-serif text-sky-800">Colores del sitio</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Ajusta la paleta pastel del sitio público. Los cambios se aplican al instante, sin
        necesidad de recompilar nada.
    </p>

    <form method="POST" action="{{ route('admin.theme.update') }}" class="mt-8 max-w-lg space-y-6">
        @csrf
        @method('PUT')

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

        <button type="submit" class="btn btn-primary">Guardar colores</button>
    </form>
</x-admin-layout>
