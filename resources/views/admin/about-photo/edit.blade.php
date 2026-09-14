<x-admin-layout title="Foto de portada">
    <h1 class="text-2xl font-serif text-sky-800">Foto de portada</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Es la foto que aparece en la sección "Sobre mí" del inicio. Sube una imagen (PNG o JPG,
        máximo 2&nbsp;MB, hasta 2000×2000&nbsp;px) o deja la foto por defecto si no subes nada.
    </p>

    <div class="mt-8 max-w-sm">
        <p class="mb-2 text-sm font-semibold text-sky-700">Vista previa actual</p>
        <div class="overflow-hidden rounded-2xl border border-paper-200 bg-white p-3">
            <img
                src="{{ !empty($photo['path']) ? \Illuminate\Support\Facades\Storage::url($photo['path']) : asset(config('site.about.photo')) }}"
                alt="Foto de portada actual"
                class="aspect-[4/5] w-full rounded-xl object-cover"
            >
        </div>
    </div>

    <form method="POST" action="{{ route('admin.about-photo.update') }}" enctype="multipart/form-data" class="mt-6 max-w-lg space-y-4">
        @csrf
        <div>
            <label for="photo" class="mb-1.5 block text-sm font-semibold text-sky-700">Nueva imagen</label>
            <input type="file" name="photo" id="photo" accept="image/*"
                class="block w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
            <p class="mt-1 text-xs text-ink-soft">Formato PNG o JPG, máximo 2&nbsp;MB, hasta 2000×2000&nbsp;px.</p>
            @error('photo')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Subir foto</button>
    </form>

    @if (!empty($photo['path']))
        <form method="POST" action="{{ route('admin.about-photo.destroy') }}" class="mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-ghost">Quitar y volver a la foto por defecto</button>
        </form>
    @endif
</x-admin-layout>
