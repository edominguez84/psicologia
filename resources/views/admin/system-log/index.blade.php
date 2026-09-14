<x-admin-layout title="Logs del sistema">
    <h1 class="text-2xl font-serif text-sky-800">Logs del sistema</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Últimas entradas de <code class="rounded bg-paper-100 px-1.5 py-0.5 text-xs">storage/logs/laravel.log</code>,
        útil para diagnosticar errores. Solo lectura.
    </p>

    @if (! $fileExists)
        <div class="mt-6 rounded-2xl border border-paper-200 bg-paper-alt p-6 text-sm text-ink-soft">
            Todavía no existe el archivo de log (no ha ocurrido ningún evento registrado).
        </div>
    @else
        <div class="mt-6 flex flex-wrap items-center gap-3" x-data="{ q: '' }">
            <form method="GET" class="flex items-center gap-2">
                <label for="lines" class="text-sm font-semibold text-sky-700">Mostrar últimas</label>
                <select name="lines" id="lines" class="rounded-xl border border-paper-200 bg-paper-50 px-3 py-2 text-sm" onchange="this.form.submit()">
                    @foreach ([50, 100, 200, 500] as $option)
                        <option value="{{ $option }}" @selected($lines === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
            <input
                type="search"
                x-model="q"
                placeholder="Buscar en las entradas mostradas…"
                class="min-w-[16rem] flex-1 rounded-xl border border-paper-200 bg-paper-50 px-4 py-2 text-sm outline-none focus:border-sky-400"
            >
            <a href="{{ request()->fullUrlWithQuery([]) }}" class="btn btn-ghost !py-2">Actualizar</a>
            <span class="text-xs text-ink-soft">Archivo: {{ $fileSizeKb }} KB</span>

            <div class="mt-2 w-full space-y-2">
                @forelse ($entries as $entry)
                    @php
                        $badge = match ($entry['level']) {
                            'error', 'critical', 'alert', 'emergency' => 'bg-clay-400/20 text-clay-500',
                            'warning' => 'bg-amber-100 text-amber-700',
                            'debug' => 'bg-paper-100 text-ink-soft',
                            default => 'bg-sky-100 text-sky-700',
                        };
                    @endphp
                    <div
                        x-show="q === '' || {{ json_encode(strtolower($entry['message'].' '.$entry['level'])) }}.includes(q.toLowerCase())"
                        class="rounded-2xl border border-paper-200 bg-paper-alt p-4"
                    >
                        <div class="flex flex-wrap items-center gap-2 text-xs text-ink-soft">
                            <span class="rounded-full px-2.5 py-1 font-semibold uppercase {{ $badge }}">{{ $entry['level'] }}</span>
                            <span>{{ $entry['date'] }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-wrap break-words font-mono text-sm text-ink">{{ $entry['message'] }}</p>
                        @if (trim($entry['extra']) !== '')
                            <details class="mt-2">
                                <summary class="cursor-pointer text-xs font-semibold text-sky-700">Ver traza completa</summary>
                                <pre class="mt-1 max-h-64 overflow-auto whitespace-pre-wrap break-words rounded-xl bg-paper-50 p-3 text-xs text-ink-soft">{{ $entry['extra'] }}</pre>
                            </details>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-paper-200 bg-paper-alt p-6 text-center text-sm text-ink-soft">
                        No hay entradas en el log todavía.
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</x-admin-layout>
