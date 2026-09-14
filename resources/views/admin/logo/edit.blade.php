<x-admin-layout title="Logo">
    <h1 class="text-2xl font-serif text-sky-800">Logo del sitio</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Sube una imagen (PNG o JPG, máximo 1&nbsp;MB, hasta 800×800&nbsp;px) o deja el logo
        por defecto —un icono de calma en tonos celestes— si no subes nada.
    </p>

    <div class="mt-8 max-w-lg">
        <p class="mb-2 text-sm font-semibold text-sky-700">Vista previa actual</p>
        <div class="rounded-2xl border border-paper-200 bg-paper-alt p-6">
            @include('partials.logo', ['class' => 'h-12 w-auto'])
        </div>
    </div>

    <form method="POST" action="{{ route('admin.logo.update') }}" enctype="multipart/form-data" class="mt-6 max-w-lg space-y-4">
        @csrf
        <div>
            <label for="logo" class="mb-1.5 block text-sm font-semibold text-sky-700">Nueva imagen</label>
            <input type="file" name="logo" id="logo" accept="image/*"
                class="block w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
            <p class="mt-1 text-xs text-ink-soft">Formato PNG o JPG, máximo 1&nbsp;MB, hasta 800×800&nbsp;px.</p>
            @error('logo')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Subir logo</button>
    </form>

    @if (!empty($logo['path']))
        <form method="POST" action="{{ route('admin.logo.destroy') }}" class="mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-ghost">Quitar logo y volver al icono por defecto</button>
        </form>
    @endif
</x-admin-layout>
