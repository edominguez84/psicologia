<x-admin-layout title="Promociones y planes">
    <h1 class="text-2xl font-serif text-sky-800">Promociones y planes</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Crea tarjetas de promoción que se muestran en la página de inicio. Al elegir un plan, la
        persona pasa por registro (si no tiene cuenta), método de pago y agendar su cita, con el
        precio de la promoción reflejado en el monto a pagar. Puedes ocultar la sección completa
        de promociones en cualquier momento desde Visibilidad de secciones.
    </p>

    <div
        x-data="{
            promotions: {{ json_encode($promotions->map(fn ($p) => ['id' => $p->id, 'title' => $p->title, 'is_active' => $p->is_active])) }},
            base: '{{ url('/admin/promotions') }}',
            moveUp(i) { if (i === 0) return; [this.promotions[i-1], this.promotions[i]] = [this.promotions[i], this.promotions[i-1]]; this.saveOrder(); },
            moveDown(i) { if (i === this.promotions.length - 1) return; [this.promotions[i+1], this.promotions[i]] = [this.promotions[i], this.promotions[i+1]]; this.saveOrder(); },
            saveOrder() {
                const ids = this.promotions.map(p => p.id);
                fetch('{{ route('admin.promotions.reorder') }}', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ids }),
                });
            },
        }"
        class="mt-8 space-y-4"
    >
        <template x-for="(promotion, i) in promotions" :key="promotion.id">
            <div class="flex items-center gap-4 rounded-2xl border border-paper-200 bg-paper-alt p-5" :class="{ 'opacity-50': !promotion.is_active }">
                <div class="flex shrink-0 flex-col gap-1.5">
                    <button type="button" @click="moveUp(i)" class="grid size-8 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-sky-50" aria-label="Subir">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" @click="moveDown(i)" class="grid size-8 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-sky-50" aria-label="Bajar">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <p class="min-w-0 flex-1 truncate font-semibold text-ink" x-text="promotion.title"></p>

                <div class="flex shrink-0 items-center gap-3 text-xs">
                    <a :href="`#editar-${promotion.id}`" class="font-semibold text-sky-700 hover:underline">Editar</a>

                    <form method="POST" :action="`${base}/${promotion.id}/toggle`">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="font-semibold hover:underline" :class="promotion.is_active ? 'text-clay-500' : 'text-sky-700'">
                            <span x-text="promotion.is_active ? 'Ocultar' : 'Activar'"></span>
                        </button>
                    </form>

                    <form method="POST" :action="`${base}/${promotion.id}`" onsubmit="return confirm('¿Eliminar esta promoción definitivamente?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-semibold text-clay-500 hover:underline">Eliminar</button>
                    </form>
                </div>
            </div>
        </template>

        <template x-if="promotions.length === 0">
            <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                Todavía no hay promociones.
            </p>
        </template>
    </div>

    {{-- Formularios de edición inline, uno por promoción, colapsados por defecto --}}
    @foreach ($promotions as $promotion)
        <details id="editar-{{ $promotion->id }}" class="mt-4 max-w-xl rounded-2xl border border-paper-200 bg-white p-5">
            <summary class="cursor-pointer text-sm font-semibold text-sky-700">Editar «{{ $promotion->title }}»</summary>
            <form method="POST" action="{{ route('admin.promotions.update', $promotion) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sky-700">Nombre de la promoción</label>
                    <input type="text" name="title" maxlength="160" required value="{{ old('title', $promotion->title) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sky-700">Precio (USD)</label>
                    <input type="number" name="price" step="0.01" min="0" required value="{{ old('price', $promotion->price) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sky-700">Descripción (qué incluye)</label>
                    <textarea name="description" rows="4" maxlength="2000" required
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">{{ old('description', $promotion->description) }}</textarea>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sky-700">Vigente hasta (opcional)</label>
                    <input type="date" name="valid_until" value="{{ old('valid_until', $promotion->valid_until?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                </div>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </form>
        </details>
    @endforeach

    <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Añadir una promoción</h2>
    <form method="POST" action="{{ route('admin.promotions.store') }}" class="max-w-xl space-y-4">
        @csrf
        <div>
            <label for="title" class="mb-1.5 block text-sm font-semibold text-sky-700">Nombre de la promoción</label>
            <input type="text" name="title" id="title" maxlength="160" required value="{{ old('title') }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="Paquete de 4 sesiones">
            @error('title')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="price" class="mb-1.5 block text-sm font-semibold text-sky-700">Precio (USD)</label>
            <input type="number" name="price" id="price" step="0.01" min="0" required value="{{ old('price') }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="120.00">
            @error('price')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="mb-1.5 block text-sm font-semibold text-sky-700">Descripción (qué incluye)</label>
            <textarea name="description" id="description" rows="4" maxlength="2000" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="4 sesiones de 50 minutos + seguimiento por WhatsApp entre sesiones.">{{ old('description') }}</textarea>
            @error('description')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="valid_until" class="mb-1.5 block text-sm font-semibold text-sky-700">Vigente hasta (opcional)</label>
            <input type="date" name="valid_until" id="valid_until" value="{{ old('valid_until') }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('valid_until')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Añadir promoción</button>
    </form>
</x-admin-layout>
