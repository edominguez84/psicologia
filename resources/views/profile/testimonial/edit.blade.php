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

    <form
        method="POST"
        action="{{ route('patient.testimonial.update') }}"
        class="mt-6 max-w-lg space-y-5"
        x-data="{ rating: {{ old('rating', $testimonial->rating ?? 0) }}, hover: 0 }"
    >
        @csrf
        @method('PUT')

        <div>
            <span class="mb-1.5 block text-sm font-semibold text-sky-700">¿Cómo calificarías la atención recibida?</span>
            <div class="flex gap-1">
                @for ($i = 1; $i <= 5; $i++)
                    <button
                        type="button"
                        @click="rating = {{ $i }}"
                        @mouseenter="hover = {{ $i }}"
                        @mouseleave="hover = 0"
                        class="text-3xl leading-none transition-colors"
                        :class="(hover || rating) >= {{ $i }} ? 'text-clay-400' : 'text-paper-200'"
                        aria-label="{{ $i }} estrella{{ $i > 1 ? 's' : '' }}"
                    >★</button>
                @endfor
            </div>
            <input type="hidden" name="rating" x-bind:value="rating" required>
            <x-input-error :messages="$errors->get('rating')" class="mt-1" />
        </div>

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
