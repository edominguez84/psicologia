<x-admin-layout title="Citas">
    <h1 class="text-2xl font-serif text-sky-800">Citas</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Aprueba o rechaza las solicitudes de los pacientes. Se les avisa por email en cuanto
        decides.
    </p>

    <div class="mt-8 overflow-x-auto rounded-2xl border border-paper-200 bg-paper-alt">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="border-b border-paper-200 text-xs font-bold uppercase tracking-wider text-sky-500">
                <tr>
                    <th class="px-4 py-3">Paciente</th>
                    <th class="px-4 py-3">Horario</th>
                    <th class="px-4 py-3">Nota</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Pago</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse ($appointments as $appointment)
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <p class="font-semibold text-ink">{{ $appointment->user->name }}</p>
                            <p class="text-xs text-ink-soft">{{ $appointment->user->email }}</p>
                        </td>
                        <td class="px-4 py-3 align-top text-ink-soft">
                            {{ $appointment->appointmentSlot->starts_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="max-w-xs px-4 py-3 align-top text-ink-soft">
                            {{ \Illuminate\Support\Str::limit($appointment->patient_note, 80) ?: '—' }}
                        </td>
                        <td class="px-4 py-3 align-top">
                            @php
                                $badgeClass = match ($appointment->status->value) {
                                    'approved' => 'bg-sky-100 text-sky-700',
                                    'rejected' => 'bg-paper-100 text-clay-500',
                                    'cancelled' => 'bg-paper-100 text-ink-soft',
                                    default => 'bg-clay-400/20 text-clay-500',
                                };
                            @endphp
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">
                                {{ $appointment->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 align-top">
                            @if ($appointment->payment_method)
                                <p class="text-xs text-ink-soft">{{ $appointment->payment_method === 'wompi' ? 'Wompi' : 'Transferencia' }}</p>
                                @if ($appointment->promotion)
                                    <p class="text-xs text-ink-soft">{{ $appointment->promotion->title }} · ${{ number_format($appointment->amount, 2) }}</p>
                                @endif
                                @if ($appointment->payment_status === 'confirmed')
                                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">Confirmado</span>
                                @elseif ($appointment->payment_status === 'reported')
                                    <form method="POST" action="{{ route('admin.appointments.confirm-payment', $appointment) }}" class="mt-1">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs font-semibold text-clay-500 hover:underline">Confirmar pago</button>
                                    </form>
                                @else
                                    <span class="rounded-full bg-paper-100 px-2 py-0.5 text-xs font-semibold text-ink-soft">Sin pagar</span>
                                @endif
                            @else
                                <span class="text-xs text-ink-soft">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            @if ($appointment->status->value === 'pending')
                                <div class="flex gap-3 text-xs">
                                    <form method="POST" action="{{ route('admin.appointments.approve', $appointment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="font-semibold text-sky-700 hover:underline">Aprobar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.appointments.reject', $appointment) }}" onsubmit="return confirm('¿Rechazar esta cita?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="font-semibold text-clay-500 hover:underline">Rechazar</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-ink-soft">
                                    {{ $appointment->decidedBy?->name ?? '—' }}
                                    @if ($appointment->decided_at)
                                        · {{ $appointment->decided_at->format('d/m/Y') }}
                                    @endif
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-ink-soft">Todavía no hay citas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
