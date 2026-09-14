<x-admin-layout title="Filtro de contenido">
    <h1 class="text-2xl font-serif text-sky-800">Filtro de contenido</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Los testimonios se revisan automáticamente contra una lista base de palabras
        inapropiadas. Aquí puedes añadir palabras extra y ajustar cuántos intentos con
        contenido inapropiado provocan la suspensión automática de una cuenta.
    </p>

    <form method="POST" action="{{ route('admin.profanity-filter.update') }}" class="mt-8 max-w-lg space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="extra_words" class="mb-1.5 block text-sm font-semibold text-sky-700">Palabras adicionales (una por línea)</label>
            <textarea name="extra_words" id="extra_words" rows="6" maxlength="2000"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="palabra1&#10;palabra2">{{ old('extra_words', implode("\n", $extraWords)) }}</textarea>
            <p class="mt-1 text-xs text-ink-soft">Se suman a una lista base ya incluida por defecto — no hace falta repetir palabras comunes.</p>
            @error('extra_words')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="ban_threshold" class="mb-1.5 block text-sm font-semibold text-sky-700">Intentos antes de suspender la cuenta automáticamente</label>
            <input type="number" name="ban_threshold" id="ban_threshold" min="1" max="20" required value="{{ old('ban_threshold', $banThreshold) }}"
                class="w-32 rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('ban_threshold')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Guardar filtro</button>
    </form>
</x-admin-layout>
