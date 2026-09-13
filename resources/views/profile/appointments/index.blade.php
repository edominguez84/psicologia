<x-patient-layout title="Mis citas">
    <h1 class="text-2xl font-serif text-sky-800">Mis citas</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Elige un horario disponible para solicitar una cita. Te avisaremos por email en cuanto se
        confirme.
    </p>

    <div class="mt-8 max-w-lg">
        <h2 class="mb-3 font-serif text-lg text-sky-800">Solicitar una cita</h2>

        @if ($availableSlots->isEmpty())
            <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                No hay horarios disponibles por ahora. Vuelve a revisar más adelante.
            </p>
        @else
            <form method="POST" action="{{ route('patient.appointments.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="appointment_slot_id" class="mb-1.5 block text-sm font-semibold text-sky-700">Horario</label>
                    <select name="appointment_slot_id" id="appointment_slot_id" required
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                        @foreach ($availableSlots as $slot)
                            <option value="{{ $slot->id }}">{{ $slot->starts_at->format('d/m/Y H:i') }} — {{ $slot->ends_at->format('H:i') }}</option>
                        @endforeach
                    </select>
                    @error('appointment_slot_id')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="patient_note" class="mb-1.5 block text-sm font-semibold text-sky-700">Nota (opcional)</label>
                    <textarea name="patient_note" id="patient_note" rows="3" maxlength="1000"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                        placeholder="Cuéntame brevemente qué necesitas...">{{ old('patient_note') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Solicitar cita</button>
            </form>
        @endif
    </div>

    <div class="mt-10">
        <h2 class="mb-3 font-serif text-lg text-sky-800">Historial de citas</h2>

        <div class="space-y-3">
            @forelse ($appointments as $appointment)
                @php
                    $badgeClass = match ($appointment->status->value) {
                        'approved' => 'bg-sky-100 text-sky-700',
                        'rejected' => 'bg-paper-100 text-clay-500',
                        'cancelled' => 'bg-paper-100 text-ink-soft',
                        default => 'bg-clay-400/20 text-clay-500',
                    };
                @endphp
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-paper-200 bg-white p-4">
                    <div>
                        <p class="font-semibold text-ink">{{ $appointment->appointmentSlot->starts_at->format('d/m/Y H:i') }}</p>
                        <span class="mt-1 inline-block rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">
                            {{ $appointment->status->label() }}
                        </span>
                    </div>
                    @if ($appointment->status->value === 'pending')
                        <form method="POST" action="{{ route('patient.appointments.cancel', $appointment) }}" onsubmit="return confirm('¿Cancelar esta cita?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-clay-500 hover:underline">Cancelar</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                    Todavía no has solicitado ninguna cita.
                </p>
            @endforelse
        </div>
    </div>
</x-patient-layout>
