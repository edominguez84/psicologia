<x-admin-layout title="Registro de auditoría">
    <h1 class="text-2xl font-serif text-sky-800">Registro de auditoría</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Historial de cambios y acciones realizadas en el sistema: quién hizo qué, cuándo y sobre qué.
    </p>

    <form method="GET" class="mt-6 flex flex-wrap gap-3">
        <select name="user" class="rounded-xl border border-paper-200 bg-paper-50 px-4 py-2 text-sm" onchange="this.form.submit()">
            <option value="">Todos los usuarios</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(($filters['user'] ?? null) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="log" class="rounded-xl border border-paper-200 bg-paper-50 px-4 py-2 text-sm" onchange="this.form.submit()">
            <option value="">Todos los tipos</option>
            @foreach ($logNames as $logName)
                <option value="{{ $logName }}" @selected(($filters['log'] ?? null) === $logName)>{{ $logName }}</option>
            @endforeach
        </select>
        @if (($filters['user'] ?? null) || ($filters['log'] ?? null))
            <a href="{{ route('admin.activity-log.index') }}" class="text-sm font-semibold text-sky-700 underline">Quitar filtros</a>
        @endif
    </form>

    <div class="mt-6 overflow-x-auto rounded-2xl border border-paper-200 bg-paper-alt">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-paper-200 text-xs font-bold uppercase tracking-wider text-sky-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Acción</th>
                    <th class="px-4 py-3">Sobre</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @forelse ($activities as $activity)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 align-top">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 align-top">{{ $activity->causer?->name ?? '—' }}</td>
                        <td class="px-4 py-3 align-top">
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $activity->log_name }}</span>
                        </td>
                        <td class="px-4 py-3 align-top">{{ $activity->description }}</td>
                        <td class="max-w-xs px-4 py-3 align-top text-ink-soft">
                            @if ($activity->subject)
                                {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                            @endif
                            @if ($activity->changes->isNotEmpty())
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-xs font-semibold text-sky-700">Ver detalle</summary>
                                    <pre class="mt-1 whitespace-pre-wrap break-words text-xs text-ink-soft">{{ json_encode($activity->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-ink-soft">Todavía no hay actividad registrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $activities->links() }}</div>
</x-admin-layout>
