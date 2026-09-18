<x-admin-layout title="Plantilla de login">
    <h1 class="text-2xl font-serif text-sky-800">Plantilla de login</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Elige cómo se ve la página de inicio de sesión (/login). El cambio no afecta el registro,
        la verificación en dos pasos ni recuperar contraseña — solo la pantalla de login.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-8 grid gap-6 sm:grid-cols-2">
        @foreach ($templates as $key => $template)
            <form method="POST" action="{{ route('admin.login-template.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="template" value="{{ $key }}">
                <button
                    type="submit"
                    class="w-full overflow-hidden rounded-2xl border bg-paper-alt text-left transition-colors hover:border-sky-300 {{ $active === $key ? 'border-sky-500 ring-2 ring-sky-500' : 'border-paper-200' }}"
                >
                    <span class="block aspect-[4/3] w-full bg-paper-200">
                        <img
                            src="{{ asset($template['preview']) }}"
                            alt="Vista previa de la plantilla {{ $template['label'] }}"
                            class="size-full object-cover object-top"
                            onerror="this.style.display='none'; this.parentElement.classList.add('grid','place-items-center'); this.insertAdjacentHTML('afterend', '&lt;span class=&quot;text-xs text-ink-soft&quot;&gt;Sin vista previa todavía&lt;/span&gt;');"
                        >
                    </span>
                    <span class="block p-4">
                        <span class="block text-base font-semibold text-ink">{{ $template['label'] }}</span>
                        <span class="mt-1 block text-sm text-ink-soft">{{ $template['description'] }}</span>
                        @if ($active === $key)
                            <span class="mt-2 inline-block rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700">
                                En uso
                            </span>
                        @else
                            <span class="mt-3 inline-block text-sm font-semibold text-sky-700">Usar esta plantilla →</span>
                        @endif
                    </span>
                </button>
            </form>
        @endforeach
    </div>

    @if ($active === 'carousel')
        <div class="mt-10 border-t border-paper-200 pt-8">
            <h2 class="font-serif text-lg text-sky-800">Imágenes del carrusel de login</h2>
            <p class="mt-1 text-sm text-ink-soft">
                Estas imágenes aparecen a la izquierda en la pantalla de inicio de sesión. Empiezan
                con 3 fotos de ejemplo — puedes reemplazarlas o agregar las tuyas.
            </p>

            <div
                x-data="{
                    images: {{ json_encode(array_values($carouselImages)) }},
                    moveUp(i) { if (i === 0) return; [this.images[i-1], this.images[i]] = [this.images[i], this.images[i-1]]; },
                    moveDown(i) { if (i === this.images.length - 1) return; [this.images[i+1], this.images[i]] = [this.images[i], this.images[i+1]]; },
                }"
                class="mt-6"
            >
                <form method="POST" action="{{ route('admin.login-carousel.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <template x-for="(image, i) in images" :key="image.path">
                        <div class="flex flex-col gap-4 rounded-2xl border border-paper-200 bg-paper-alt p-4 sm:flex-row sm:items-center">
                            <img :src="image.url" :alt="image.alt" class="h-24 w-full shrink-0 rounded-xl object-cover sm:w-40">

                            <div class="min-w-0 flex-1">
                                <label class="mb-1 block text-xs font-semibold text-ink-soft">Texto alternativo (descripción breve)</label>
                                <input type="text" :name="`images[${i}][alt]`" x-model="image.alt"
                                    class="w-full rounded-lg border border-paper-200 bg-paper-50 px-3 py-2 text-sm outline-none focus:border-sky-400">
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
                            Todavía no hay imágenes en el carrusel.
                        </p>
                    </template>

                    <button type="submit" class="btn btn-primary">Guardar orden y textos</button>
                </form>
            </div>

            <h3 class="mb-3 mt-8 font-serif text-base text-sky-800">Añadir una imagen</h3>
            <form method="POST" action="{{ route('admin.login-carousel.store') }}" enctype="multipart/form-data" class="max-w-lg space-y-4">
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
                <button type="submit" class="btn btn-primary">Añadir al carrusel</button>
            </form>

            @if (!empty($carouselImages))
                <h3 class="mb-3 mt-8 font-serif text-base text-sky-800">Eliminar una imagen</h3>
                <div class="max-w-lg space-y-2">
                    @foreach ($carouselImages as $i => $image)
                        <form method="POST" action="{{ route('admin.login-carousel.destroy', $i) }}" class="flex items-center justify-between gap-3 rounded-xl border border-paper-200 bg-paper-alt px-4 py-2.5">
                            @csrf
                            @method('DELETE')
                            <span class="truncate text-sm text-ink-soft">{{ $image['alt'] ?: $image['path'] }}</span>
                            <button type="submit" class="shrink-0 text-xs font-semibold text-clay-500 hover:underline">Eliminar</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-admin-layout>
