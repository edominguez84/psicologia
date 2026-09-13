<x-admin-layout title="Editar sección">
    <h1 class="text-2xl font-serif text-sky-800">Editar sección</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Los cambios se reflejan al instante en la página de inicio.
    </p>

    @if ($section->image_path)
        <div class="mt-6 max-w-xl">
            <p class="mb-1.5 text-sm font-semibold text-sky-700">Imagen actual</p>
            <img src="{{ \Illuminate\Support\Facades\Storage::url($section->image_path) }}" alt="" class="w-full max-w-sm rounded-2xl border border-paper-200 object-cover">
        </div>
    @endif

    <form method="POST" action="{{ route('admin.custom-sections.update', $section) }}" enctype="multipart/form-data" class="mt-6 max-w-xl space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label for="title" class="mb-1.5 block text-sm font-semibold text-sky-700">Título</label>
            <input type="text" name="title" id="title" maxlength="160" required value="{{ old('title', $section->title) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('title')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="body" class="mb-1.5 block text-sm font-semibold text-sky-700">Texto</label>
            <textarea name="body" id="body" rows="6" maxlength="4000" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">{{ old('body', $section->body) }}</textarea>
            @error('body')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="image" class="mb-1.5 block text-sm font-semibold text-sky-700">Reemplazar imagen (opcional, máx. 2&nbsp;MB, hasta 2000×2000&nbsp;px)</label>
            <input type="file" name="image" id="image" accept="image/*"
                class="block w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
            @error('image')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-4">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('admin.custom-sections.index') }}" class="text-sm font-semibold text-ink-soft hover:underline">Volver al listado</a>
        </div>
    </form>
</x-admin-layout>
