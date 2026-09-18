<x-admin-layout title="Plantilla de la landing">
    <h1 class="text-2xl font-serif text-sky-800">Plantilla de la landing</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Elige cómo se organiza visualmente la página principal del sitio. Las 3 plantillas
        muestran exactamente el mismo contenido que ya cargaste (textos, testimonios, promociones,
        preguntas frecuentes, etc.) y respetan las secciones que ocultaste en
        <a href="{{ route('admin.section-visibility.edit') }}" class="font-semibold text-sky-700 underline">Visibilidad de secciones</a> —
        solo cambia el orden y el diseño en que se presentan. El cambio es instantáneo.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($templates as $key => $template)
            <form method="POST" action="{{ route('admin.landing-template.update') }}">
                @csrf
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
</x-admin-layout>
