<x-admin-layout title="Panel">
    <h1 class="text-2xl font-serif text-sky-800">Panel de administración</h1>
    <p class="mt-2 text-sm text-ink-soft">Resumen rápido y accesos a las secciones editables del sitio.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.messages.index') }}" class="rounded-2xl border border-paper-200 bg-white p-5 transition-colors hover:border-sky-300">
            <p class="text-3xl font-serif text-sky-800">{{ $unhandledContacts }}</p>
            <p class="mt-1 text-sm font-semibold text-ink-soft">Mensajes sin atender</p>
        </a>
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <p class="text-3xl font-serif text-sky-800">{{ $totalContacts }}</p>
            <p class="mt-1 text-sm font-semibold text-ink-soft">Mensajes de contacto totales</p>
        </div>
        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <p class="text-3xl font-serif text-sky-800">{{ $totalCheckups }}</p>
            <p class="mt-1 text-sm font-semibold text-ink-soft">Chequeos emocionales realizados</p>
        </div>
    </div>

    <div class="mt-10 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.theme.edit') }}" class="rounded-2xl border border-paper-200 bg-white p-5 hover:border-sky-300">
            <h2 class="font-serif text-lg text-sky-800">Colores del sitio</h2>
            <p class="mt-1 text-sm text-ink-soft">Cambia la paleta pastel sin tocar código.</p>
        </a>
        <a href="{{ route('admin.logo.edit') }}" class="rounded-2xl border border-paper-200 bg-white p-5 hover:border-sky-300">
            <h2 class="font-serif text-lg text-sky-800">Logo</h2>
            <p class="mt-1 text-sm text-ink-soft">Sube tu propio logo o usa el que viene por defecto.</p>
        </a>
        <a href="{{ route('admin.contact.edit') }}" class="rounded-2xl border border-paper-200 bg-white p-5 hover:border-sky-300">
            <h2 class="font-serif text-lg text-sky-800">Datos de contacto</h2>
            <p class="mt-1 text-sm text-ink-soft">WhatsApp, email y zona de atención.</p>
        </a>
        <a href="{{ route('admin.content.edit', 'hero') }}" class="rounded-2xl border border-paper-200 bg-white p-5 hover:border-sky-300">
            <h2 class="font-serif text-lg text-sky-800">Textos del sitio</h2>
            <p class="mt-1 text-sm text-ink-soft">Hero, servicios, testimonios, FAQ y más.</p>
        </a>
    </div>

    @if ($recentCheckups->isNotEmpty())
        <div class="mt-10">
            <h2 class="font-serif text-lg text-sky-800">Últimos chequeos emocionales</h2>
            <div class="mt-3 overflow-x-auto rounded-2xl border border-paper-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-paper-200 text-xs font-bold uppercase tracking-wider text-sky-500">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Puntuación</th>
                            <th class="px-4 py-3">Nivel</th>
                            <th class="px-4 py-3">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-paper-100">
                        @foreach ($recentCheckups as $checkup)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">{{ $checkup->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $checkup->score }}/15</td>
                                <td class="px-4 py-3 capitalize">{{ $checkup->band }}</td>
                                <td class="px-4 py-3">{{ $checkup->email ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-admin-layout>
