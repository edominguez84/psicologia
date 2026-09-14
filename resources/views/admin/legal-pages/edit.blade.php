<x-admin-layout title="Páginas legales">
    <h1 class="text-2xl font-serif text-sky-800">Páginas legales</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Edita el texto de la Política de privacidad y las Condiciones de uso del sitio público.
        Puedes usar negrita, cursiva, listas y subtítulos.
    </p>

    <div class="mt-6 flex gap-2 border-b border-paper-200">
        @foreach (\App\Http\Controllers\Admin\LegalPageController::PAGES as $slug)
            @php
                $label = $slug === 'privacy' ? 'Política de privacidad' : 'Condiciones de uso';
            @endphp
            <a
                href="{{ route('admin.legal.edit', $slug) }}"
                class="border-b-2 px-4 py-2.5 text-sm font-semibold {{ $slug === $page ? 'border-sky-600 text-sky-700' : 'border-transparent text-ink-soft hover:text-sky-700' }}"
            >{{ $label }}</a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.legal.update', $page) }}" class="mt-8 max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="title" class="mb-1.5 block text-sm font-semibold text-sky-700">Título de la página</label>
            <input
                type="text"
                name="title"
                id="title"
                value="{{ old('title', $title) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
            >
            @error('title')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="body_html" class="mb-1.5 block text-sm font-semibold text-sky-700">Contenido</label>
            <input type="hidden" name="body_html" id="body_html" value="{{ old('body_html', $bodyHtml) }}">
            <trix-editor input="body_html" class="min-h-[24rem] rounded-xl border border-paper-200 bg-paper-alt px-4 py-3 text-sm"></trix-editor>
            @error('body_html')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Guardar página</button>
    </form>

    <p class="mt-6 max-w-3xl text-xs text-ink-soft">
        Vista pública: <a class="text-sky-700 underline" href="{{ $page === 'privacy' ? route('privacy') : route('terms') }}" target="_blank" rel="noopener">
            {{ $page === 'privacy' ? url('/privacidad') : url('/condiciones-de-uso') }}
        </a>
    </p>
</x-admin-layout>
