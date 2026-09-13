<x-admin-layout title="Visibilidad de secciones">
    <h1 class="text-2xl font-serif text-sky-800">Visibilidad de secciones</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Elige qué secciones de la página de inicio se muestran a los visitantes. Ocultar una
        sección no borra su contenido — puedes volver a mostrarla cuando quieras. La portada y la
        sección de contacto siempre están visibles.
    </p>

    <div
        x-data="{
            initial: {{ json_encode($visibility) }},
            current: {{ json_encode($visibility) }},
            confirmBeforeSubmit(event) {
                const hiding = Object.keys(this.current).filter(
                    key => this.initial[key] && !this.current[key]
                );
                if (hiding.length === 0) return true;

                const labels = {{ json_encode(collect($sections)->map(fn ($s) => $s['label'])) }};
                const names = hiding.map(key => labels[key] ?? key).join(', ');
                if (!confirm(`Vas a ocultar: ${names}. Los visitantes ya no verán estas secciones ni podrán navegar a ellas desde el menú. ¿Confirmas?`)) {
                    event.preventDefault();
                    return false;
                }
                return true;
            },
        }"
    >
        <form
            method="POST"
            action="{{ route('admin.section-visibility.update') }}"
            class="mt-8 max-w-lg space-y-3"
            @submit="confirmBeforeSubmit($event)"
        >
            @csrf
            @method('PUT')

            @foreach ($sections as $key => $section)
                <label class="flex items-center justify-between gap-4 rounded-2xl border border-paper-200 bg-white px-4 py-3">
                    <span class="text-sm font-semibold text-ink">{{ $section['label'] }}</span>
                    <input
                        type="checkbox"
                        name="visible[]"
                        value="{{ $key }}"
                        x-model="current['{{ $key }}']"
                        class="size-5 rounded border-paper-200 accent-sky-600"
                        @checked($visibility[$key])
                    >
                </label>
            @endforeach

            <button type="submit" class="btn btn-primary">Guardar visibilidad</button>
        </form>
    </div>
</x-admin-layout>
