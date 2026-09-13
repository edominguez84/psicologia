<x-admin-layout title="Testimonios de pacientes">
    <h1 class="text-2xl font-serif text-sky-800">Testimonios de pacientes</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Aprueba los testimonios que quieras mostrar en la sección pública de testimonios (se
        muestran junto a los de ejemplo ya existentes). Solo los aprobados son visibles.
    </p>

    <div class="mt-8 space-y-4">
        @forelse ($testimonials as $testimonial)
            <div class="rounded-2xl border border-paper-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">{{ $testimonial->user->name }}</p>
                        <p class="text-xs text-ink-soft">{{ $testimonial->user->email }} · {{ $testimonial->created_at->format('d/m/Y H:i') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $testimonial->text }}</p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2 text-xs">
                        @if ($testimonial->is_approved)
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 font-semibold text-sky-700">Aprobado</span>
                            <form method="POST" action="{{ route('admin.testimonials.unapprove', $testimonial) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="font-semibold text-clay-500 hover:underline">Ocultar</button>
                            </form>
                        @else
                            <span class="rounded-full bg-paper-100 px-2.5 py-1 font-semibold text-clay-500">Pendiente</span>
                            <form method="POST" action="{{ route('admin.testimonials.approve', $testimonial) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="font-semibold text-sky-700 hover:underline">Aprobar</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" onsubmit="return confirm('¿Eliminar este testimonio definitivamente?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold text-clay-500 hover:underline">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                Todavía no hay testimonios de pacientes.
            </p>
        @endforelse
    </div>
</x-admin-layout>
