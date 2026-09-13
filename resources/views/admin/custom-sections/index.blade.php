<x-admin-layout title="Secciones personalizadas">
    <h1 class="text-2xl font-serif text-sky-800">Secciones personalizadas</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Añade bloques propios (título, texto e imagen opcional) que se muestran en la página de
        inicio, justo antes del formulario de contacto, en el orden que definas aquí. Ocultar una
        sección no borra su contenido — puedes volver a mostrarla cuando quieras.
    </p>

    <div
        x-data="{
            sections: {{ json_encode($sections->map(fn ($s) => ['id' => $s->id, 'title' => $s->title, 'is_active' => $s->is_active])) }},
            base: '{{ url('/admin/custom-sections') }}',
            moveUp(i) { if (i === 0) return; [this.sections[i-1], this.sections[i]] = [this.sections[i], this.sections[i-1]]; this.saveOrder(); },
            moveDown(i) { if (i === this.sections.length - 1) return; [this.sections[i+1], this.sections[i]] = [this.sections[i], this.sections[i+1]]; this.saveOrder(); },
            saveOrder() {
                const ids = this.sections.map(s => s.id);
                fetch('{{ route('admin.custom-sections.reorder') }}', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ids }),
                });
            },
        }"
        class="mt-8 space-y-4"
    >
        <template x-for="(section, i) in sections" :key="section.id">
            <div class="flex items-center gap-4 rounded-2xl border border-paper-200 bg-white p-5" :class="{ 'opacity-50': !section.is_active }">
                <div class="flex shrink-0 flex-col gap-1.5">
                    <button type="button" @click="moveUp(i)" class="grid size-8 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-sky-50" aria-label="Subir">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" @click="moveDown(i)" class="grid size-8 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-sky-50" aria-label="Bajar">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <p class="min-w-0 flex-1 truncate font-semibold text-ink" x-text="section.title"></p>

                <div class="flex shrink-0 items-center gap-3 text-xs">
                    <a :href="`${base}/${section.id}/edit`" class="font-semibold text-sky-700 hover:underline">Editar</a>

                    <form method="POST" :action="`${base}/${section.id}/toggle`">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="font-semibold hover:underline" :class="section.is_active ? 'text-clay-500' : 'text-sky-700'">
                            <span x-text="section.is_active ? 'Ocultar' : 'Activar'"></span>
                        </button>
                    </form>

                    <form method="POST" :action="`${base}/${section.id}`" onsubmit="return confirm('¿Eliminar esta sección definitivamente? No se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-semibold text-clay-500 hover:underline">Eliminar</button>
                    </form>
                </div>
            </div>
        </template>

        <template x-if="sections.length === 0">
            <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                Todavía no hay secciones personalizadas.
            </p>
        </template>
    </div>

    <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Añadir una sección</h2>
    <form method="POST" action="{{ route('admin.custom-sections.store') }}" enctype="multipart/form-data" class="max-w-xl space-y-4">
        @csrf
        <div>
            <label for="title" class="mb-1.5 block text-sm font-semibold text-sky-700">Título</label>
            <input type="text" name="title" id="title" maxlength="160" required value="{{ old('title') }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="Talleres grupales">
            @error('title')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="body" class="mb-1.5 block text-sm font-semibold text-sky-700">Texto</label>
            <textarea name="body" id="body" rows="5" maxlength="4000" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="Cuéntales de qué trata esta sección...">{{ old('body') }}</textarea>
            @error('body')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="image" class="mb-1.5 block text-sm font-semibold text-sky-700">Imagen (opcional, máx. 2&nbsp;MB, hasta 2000×2000&nbsp;px)</label>
            <input type="file" name="image" id="image" accept="image/*"
                class="block w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
            @error('image')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Añadir sección</button>
    </form>
</x-admin-layout>
