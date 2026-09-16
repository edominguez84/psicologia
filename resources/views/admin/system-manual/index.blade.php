<x-admin-layout title="Manual del sistema">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-serif text-sky-800">Manual del sistema</h1>
            <p class="mt-2 text-sm text-ink-soft">
                Explica la dinámica de la plataforma entre roles (super administrador, administrador y
                paciente) y el recorrido completo para hacer efectiva una cita.
            </p>
        </div>
        <a href="{{ route('admin.system-manual.download') }}" class="btn btn-primary">Descargar PDF</a>
    </div>

    <div class="mt-8 max-w-2xl space-y-4">
        <div class="rounded-2xl border border-paper-200 bg-paper-alt p-5">
            <h2 class="font-serif text-lg text-sky-800">Contenido del manual</h2>
            <p class="mt-2 text-sm text-ink-soft">
                Capturas de pantalla reales del recorrido completo de una cita: registro y verificación
                del paciente, elección de horario y pago, y la aprobación desde el panel de
                administración.
            </p>
        </div>

        <p class="text-xs text-ink-soft">
            El PDF se genera al momento de la descarga — compártelo con el personal nuevo o con quien
            necesite entender el flujo completo de citas.
        </p>
    </div>
</x-admin-layout>
