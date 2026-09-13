<x-admin-layout title="Icono del sitio">
    <h1 class="text-2xl font-serif text-sky-800">Icono del sitio (favicon)</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Es el icono pequeño que aparece en la pestaña del navegador. Sube una imagen cuadrada
        (formato PNG, JPG o ICO, máximo 512&nbsp;KB, hasta 512×512&nbsp;px) o deja el icono por
        defecto si no subes nada.
    </p>

    <div class="mt-8 max-w-lg">
        <p class="mb-2 text-sm font-semibold text-sky-700">Vista previa actual</p>
        <div class="flex items-center gap-4 rounded-2xl border border-paper-200 bg-white p-6">
            <img
                src="{{ ! empty($favicon['path']) ? \Illuminate\Support\Facades\Storage::url($favicon['path']) : asset('favicon.ico') }}"
                alt="Icono actual"
                class="size-10 rounded border border-paper-200 object-contain"
            >
            <span class="text-sm text-ink-soft">Así se ve en la pestaña del navegador.</span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.favicon.update') }}" enctype="multipart/form-data" class="mt-6 max-w-lg space-y-4">
        @csrf
        <div>
            <label for="favicon" class="mb-1.5 block text-sm font-semibold text-sky-700">Nueva imagen</label>
            <input type="file" name="favicon" id="favicon" accept="image/png,image/jpeg,.ico"
                class="block w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
            @error('favicon')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Subir icono</button>
    </form>

    @if (!empty($favicon['path']))
        <form method="POST" action="{{ route('admin.favicon.destroy') }}" class="mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-ghost">Quitar y volver al icono por defecto</button>
        </form>
    @endif
</x-admin-layout>
