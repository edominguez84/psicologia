<x-admin-layout title="Informe del sistema">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-serif text-sky-800">Informe del sistema</h1>
            <p class="mt-2 text-sm text-ink-soft">
                Resumen de citas, mensajes, testimonios, usuarios y errores recientes del sistema.
            </p>
        </div>
        <a href="{{ route('admin.reports.download') }}" class="btn btn-primary">Descargar PDF</a>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-paper-200 bg-paper-alt p-5">
            <h2 class="font-serif text-lg text-sky-800">Citas por estado</h2>
            <dl class="mt-3 space-y-1 text-sm">
                @foreach ($appointmentsByStatus as $label => $count)
                    <div class="flex justify-between"><dt class="text-ink-soft">{{ $label }}</dt><dd class="font-semibold text-ink">{{ $count }}</dd></div>
                @endforeach
            </dl>
        </div>

        <div class="rounded-2xl border border-paper-200 bg-paper-alt p-5">
            <h2 class="font-serif text-lg text-sky-800">Mensajes de contacto</h2>
            <dl class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-ink-soft">Sin atender</dt><dd class="font-semibold text-ink">{{ $unhandledContacts }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Total</dt><dd class="font-semibold text-ink">{{ $totalContacts }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl border border-paper-200 bg-paper-alt p-5">
            <h2 class="font-serif text-lg text-sky-800">Testimonios</h2>
            <dl class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-ink-soft">Pendientes</dt><dd class="font-semibold text-ink">{{ $pendingTestimonials }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Aprobados</dt><dd class="font-semibold text-ink">{{ $approvedTestimonials }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl border border-paper-200 bg-paper-alt p-5">
            <h2 class="font-serif text-lg text-sky-800">Usuarios por rol</h2>
            <dl class="mt-3 space-y-1 text-sm">
                @foreach ($usersByRole as $label => $count)
                    <div class="flex justify-between"><dt class="text-ink-soft">{{ $label }}</dt><dd class="font-semibold text-ink">{{ $count }}</dd></div>
                @endforeach
                <div class="flex justify-between border-t border-paper-200 pt-1"><dt class="text-ink-soft">Suspendidos</dt><dd class="font-semibold text-clay-500">{{ $bannedUsers }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl border border-paper-200 bg-paper-alt p-5">
            <h2 class="font-serif text-lg text-sky-800">Chequeos emocionales</h2>
            <dl class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-ink-soft">Este mes</dt><dd class="font-semibold text-ink">{{ $checkupsThisMonth }}</dd></div>
            </dl>
        </div>
    </div>

    <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Errores recientes del sistema</h2>
    @if ($errorEntries->isEmpty())
        <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
            No hay errores registrados en el rango revisado.
        </p>
    @else
        <div class="space-y-3">
            @foreach ($errorEntries as $entry)
                <div class="rounded-2xl border border-paper-200 bg-white p-4">
                    <div class="flex items-center gap-2 text-xs text-ink-soft">
                        <span class="rounded-full bg-clay-400/20 px-2.5 py-1 font-semibold uppercase text-clay-500">{{ $entry['level'] }}</span>
                        <span>{{ $entry['date'] }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-wrap break-words font-mono text-sm text-ink">{{ $entry['message'] }}</p>
                </div>
            @endforeach
        </div>
    @endif
</x-admin-layout>
