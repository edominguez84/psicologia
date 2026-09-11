<x-admin-layout title="Redes sociales">
    <h1 class="text-2xl font-serif text-sky-800">Redes sociales</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Pega la URL completa de cada red donde estés presente. Deja el campo vacío para que
        ese icono no aparezca en el sitio.
    </p>

    <form method="POST" action="{{ route('admin.social.update') }}" class="mt-8 max-w-lg space-y-5">
        @csrf
        @method('PUT')

        @foreach ($networks as $key => $label)
            <div>
                <label for="{{ $key }}" class="mb-1.5 block text-sm font-semibold text-sky-700">{{ $label }}</label>
                <input
                    type="url"
                    name="{{ $key }}"
                    id="{{ $key }}"
                    value="{{ old($key, $links[$key] ?? '') }}"
                    placeholder="https://..."
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                >
                @error($key)
                    <p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary">Guardar redes sociales</button>
    </form>
</x-admin-layout>
