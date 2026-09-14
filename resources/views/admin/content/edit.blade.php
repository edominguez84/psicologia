<x-admin-layout :title="$schema['label']">
    <h1 class="text-2xl font-serif text-sky-800">{{ $schema['label'] }}</h1>
    <p class="mt-2 text-sm text-ink-soft">Estos textos se muestran tal cual en el sitio público.</p>

    <form method="POST" action="{{ route('admin.content.update', $sectionKey) }}" class="mt-8 space-y-8">
        @csrf
        @method('PUT')

        @foreach ($schema['fields'] as $key => $field)
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-sky-700">{{ $field['label'] }}</label>

                @if ($field['type'] === 'text')
                    <input type="text" name="{{ $key }}" value="{{ old($key, $values[$key] ?? '') }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">

                @elseif ($field['type'] === 'textarea')
                    <textarea name="{{ $key }}" rows="3"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">{{ old($key, $values[$key] ?? '') }}</textarea>

                @elseif ($field['type'] === 'list')
                    <textarea name="{{ $key }}" rows="5"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                    >{{ old($key, implode("\n", $values[$key] ?? [])) }}</textarea>
                    <p class="mt-1 text-xs text-ink-soft">Una línea por elemento.</p>

                @elseif ($field['type'] === 'repeater')
                    <div
                        x-data="{
                            items: {{ json_encode(array_values($values[$key] ?? [])) }},
                            add() { this.items.push({{ json_encode(array_fill_keys(array_keys($field['fields']), '')) }}); },
                            remove(i) { this.items.splice(i, 1); },
                        }"
                        class="space-y-4"
                    >
                        <template x-for="(item, i) in items" :key="i">
                            <div class="rounded-2xl border border-paper-200 bg-paper-alt p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-sky-500" x-text="'#' + (i + 1)"></span>
                                    <button type="button" @click="remove(i)" class="text-xs font-semibold text-clay-500 hover:underline">Eliminar</button>
                                </div>
                                <div class="space-y-3">
                                    @foreach ($field['fields'] as $subKey => $subField)
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-ink-soft">{{ $subField['label'] }}</label>
                                            @if ($subField['type'] === 'textarea')
                                                <textarea :name="`{{ $key }}[${i}][{{ $subKey }}]`" x-model="item.{{ $subKey }}" rows="2"
                                                    class="w-full rounded-lg border border-paper-200 bg-paper-50 px-3 py-2 text-sm outline-none focus:border-sky-400"></textarea>
                                            @else
                                                <input type="text" :name="`{{ $key }}[${i}][{{ $subKey }}]`" x-model="item.{{ $subKey }}"
                                                    class="w-full rounded-lg border border-paper-200 bg-paper-50 px-3 py-2 text-sm outline-none focus:border-sky-400">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="add()" class="btn btn-ghost text-sm">+ Añadir</button>
                    </div>
                @endif

                @error($key)
                    <p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </form>
</x-admin-layout>
