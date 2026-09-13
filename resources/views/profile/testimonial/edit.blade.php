<x-patient-layout title="Mi testimonio">
    <h1 class="text-2xl font-serif text-sky-800">Mi testimonio</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Comparte tu experiencia. Antes de publicarse en el sitio, cada testimonio pasa por una
        revisión — si editas uno ya aprobado, volverá a quedar pendiente de revisión.
    </p>

    @if ($testimonial)
        <div class="mt-6 max-w-lg">
            @if ($testimonial->is_approved)
                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Publicado en el sitio</span>
            @else
                <span class="rounded-full bg-paper-100 px-2.5 py-1 text-xs font-semibold text-clay-500">Pendiente de revisión</span>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('patient.testimonial.update') }}" class="mt-6 max-w-lg space-y-5">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="text" value="Tu testimonio" />
            <textarea id="text" name="text" rows="6" maxlength="1000" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="Cuéntanos cómo ha sido tu experiencia...">{{ old('text', $testimonial->text ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('text')" class="mt-1" />
        </div>

        <x-primary-button>Guardar testimonio</x-primary-button>
    </form>
</x-patient-layout>
