<x-admin-layout title="Horarios de llamada gratis">
    <h1 class="text-2xl font-serif text-sky-800">Horarios de llamada gratis</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Crea las franjas horarias disponibles para la llamada gratuita de 15 minutos. Es un
        catálogo aparte de los horarios de citas pagadas — el botón de "Reserva una llamada
        gratis" solo se muestra en el sitio si hay al menos un horario libre aquí.
    </p>

    <div class="mt-8 space-y-3">
        @forelse ($slots as $slot)
            <div class="flex items-center justify-between gap-4 rounded-2xl border border-paper-200 bg-paper-alt p-4" @class(['opacity-50' => ! $slot->is_active])>
                <div>
                    <p class="font-semibold text-ink">{{ $slot->starts_at->format('d/m/Y H:i') }} — {{ $slot->ends_at->format('H:i') }}</p>
                    @if ($slot->contact_messages_count > 0)
                        <p class="text-xs font-semibold text-clay-500">Ya reservado</p>
                    @elseif ($slot->starts_at->isPast())
                        <p class="text-xs text-ink-soft">Ya pasó</p>
                    @else
                        <p class="text-xs text-sky-700">Libre</p>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-3 text-xs">
                    <form method="POST" action="{{ route('admin.call-slots.toggle', $slot) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="font-semibold hover:underline {{ $slot->is_active ? 'text-clay-500' : 'text-sky-700' }}">
                            {{ $slot->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.call-slots.destroy', $slot) }}" onsubmit="return confirm('¿Eliminar este horario definitivamente?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-semibold text-clay-500 hover:underline">Eliminar</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                Todavía no hay horarios configurados — mientras tanto, el botón de llamada gratis
                no se muestra en el sitio.
            </p>
        @endforelse
    </div>

    <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Añadir un horario</h2>
    <form method="POST" action="{{ route('admin.call-slots.store') }}" class="max-w-lg space-y-4">
        @csrf
        <div>
            <label for="starts_at" class="mb-1.5 block text-sm font-semibold text-sky-700">Inicio</label>
            <input type="datetime-local" name="starts_at" id="starts_at" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('starts_at')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="ends_at" class="mb-1.5 block text-sm font-semibold text-sky-700">Fin</label>
            <input type="datetime-local" name="ends_at" id="ends_at" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('ends_at')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Añadir horario</button>
    </form>
</x-admin-layout>
