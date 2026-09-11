<x-admin-layout title="Mensajes">
    <h1 class="text-2xl font-serif text-sky-800">Mensajes recibidos</h1>
    <p class="mt-2 text-sm text-ink-soft">Formularios de contacto y chequeos de bienestar emocional guardados en la base de datos.</p>

    <h2 class="mb-3 mt-8 font-serif text-lg text-sky-800">Formulario de contacto</h2>
    <div class="overflow-x-auto rounded-2xl border border-paper-200 bg-white">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="border-b border-paper-200 text-xs font-bold uppercase tracking-wider text-sky-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Contacto</th>
                    <th class="px-4 py-3">Mensaje</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse ($contactMessages as $message)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 align-top">{{ $message->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 align-top">
                            <p class="font-semibold text-ink">{{ $message->name }}</p>
                            <p class="text-xs text-ink-soft">{{ $message->subject }}</p>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <p>{{ $message->email }}</p>
                            <p class="text-xs text-ink-soft">{{ $message->phone }}</p>
                        </td>
                        <td class="max-w-xs px-4 py-3 align-top text-ink-soft">{{ \Illuminate\Support\Str::limit($message->message, 120) }}</td>
                        <td class="px-4 py-3 align-top">
                            @if ($message->handled_at)
                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Atendido</span>
                            @else
                                <span class="rounded-full bg-paper-100 px-2.5 py-1 text-xs font-semibold text-clay-500">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            <form method="POST" action="{{ route('admin.messages.contact.handle', $message) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs font-semibold text-sky-700 hover:underline">
                                    {{ $message->handled_at ? 'Marcar pendiente' : 'Marcar atendido' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-ink-soft">Todavía no hay mensajes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $contactMessages->links() }}</div>

    <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Chequeos de bienestar emocional</h2>
    <div class="overflow-x-auto rounded-2xl border border-paper-200 bg-white">
        <table class="w-full min-w-[480px] text-left text-sm">
            <thead class="border-b border-paper-200 text-xs font-bold uppercase tracking-wider text-sky-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Puntuación</th>
                    <th class="px-4 py-3">Nivel</th>
                    <th class="px-4 py-3">Email</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse ($checkups as $checkup)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ $checkup->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $checkup->score }}/15</td>
                        <td class="px-4 py-3 capitalize">{{ $checkup->band }}</td>
                        <td class="px-4 py-3">{{ $checkup->email ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-ink-soft">Todavía no hay chequeos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $checkups->links() }}</div>
</x-admin-layout>
