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
            <ul class="mt-3 space-y-1.5 text-sm text-ink-soft">
                <li>1. Roles del sistema (super administrador, administrador/editor, paciente)</li>
                <li>2. Cómo un paciente hace efectiva una cita — recorrido completo paso a paso</li>
                <li>3. La llamada gratuita de 15 minutos (sin cuenta ni pago)</li>
                <li>4. Configuración que hace posible este flujo (solo super admin)</li>
                <li>5. Idioma del sitio</li>
            </ul>
        </div>

        <p class="text-xs text-ink-soft">
            El PDF se genera al momento de la descarga con el contenido más reciente de este manual —
            compártelo con el personal nuevo o con quien necesite entender el flujo completo de citas.
        </p>
    </div>
</x-admin-layout>
