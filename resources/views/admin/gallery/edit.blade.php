<x-admin-layout title="Galería">
    <h1 class="text-2xl font-serif text-sky-800">Galería del carrusel de inicio</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Estas imágenes aparecen en el carrusel justo debajo de la portada. Usa las flechas para
        reordenarlas y guarda los cambios; para quitar una imagen de fábrica solo se elimina de
        la lista, para quitar una que tú subiste también se borra el archivo.
    </p>

    <div
        x-data="{
            images: {{ json_encode(array_values($images)) }},
            moveUp(i) { if (i === 0) return; [this.images[i-1], this.images[i]] = [this.images[i], this.images[i-1]]; },
            moveDown(i) { if (i === this.images.length - 1) return; [this.images[i+1], this.images[i]] = [this.images[i], this.images[i+1]]; },
        }"
        class="mt-8"
    >
        <form method="POST" action="{{ route('admin.gallery.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <template x-for="(image, i) in images" :key="image.path">
                <div class="flex flex-col gap-4 rounded-2xl border border-paper-200 bg-white p-4 sm:flex-row sm:items-center">
                    <img
                        :src="image.url"
                        :alt="image.alt"
                        class="h-24 w-full shrink-0 rounded-xl object-cover sm:w-40"
                    >

                    <div class="min-w-0 flex-1">
                        <label class="mb-1 block text-xs font-semibold text-ink-soft">Texto alternativo (descripción breve)</label>
                        <input
                            type="text"
                            :name="`images[${i}][alt]`"
                            x-model="image.alt"
                            class="w-full rounded-lg border border-paper-200 bg-paper-50 px-3 py-2 text-sm outline-none focus:border-sky-400"
                        >
                        <input type="hidden" :name="`images[${i}][path]`" :value="image.path">
                    </div>

                    <div class="flex shrink-0 gap-2 sm:flex-col">
                        <button type="button" @click="moveUp(i)" class="grid size-9 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-sky-50" aria-label="Subir">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" @click="moveDown(i)" class="grid size-9 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-sky-50" aria-label="Bajar">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="images.length === 0">
                <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                    Todavía no hay imágenes en la galería.
                </p>
            </template>

            <button type="submit" class="btn btn-primary">Guardar orden y textos</button>
        </form>
    </div>

    <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Añadir una imagen</h2>
    <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data" class="max-w-lg space-y-4">
        @csrf
        <div>
            <label for="image" class="mb-1.5 block text-sm font-semibold text-sky-700">Imagen (PNG o JPG, máx. 2&nbsp;MB, hasta 2400×2400&nbsp;px)</label>
            <input type="file" name="image" id="image" accept="image/*"
                class="block w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
            @error('image')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="alt" class="mb-1.5 block text-sm font-semibold text-sky-700">Texto alternativo (opcional)</label>
            <input type="text" name="alt" id="alt"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
        </div>
        <button type="submit" class="btn btn-primary">Añadir a la galería</button>
    </form>

    @if (!empty($images))
        <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Eliminar una imagen</h2>
        <div class="max-w-lg space-y-2">
            @foreach ($images as $i => $image)
                <form method="POST" action="{{ route('admin.gallery.destroy', $i) }}" class="flex items-center justify-between gap-3 rounded-xl border border-paper-200 bg-white px-4 py-2.5">
                    @csrf
                    @method('DELETE')
                    <span class="truncate text-sm text-ink-soft">{{ $image['alt'] ?: $image['path'] }}</span>
                    <button type="submit" class="shrink-0 text-xs font-semibold text-clay-500 hover:underline">Eliminar</button>
                </form>
            @endforeach
        </div>
    @endif
</x-admin-layout>
