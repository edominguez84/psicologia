<x-admin-layout title="Formulario de contacto">
    <h1 class="text-2xl font-serif text-sky-800">Formulario de contacto</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Elige qué campos mostrar en el formulario y añade tus propios campos personalizados.
        Nombre, email y mensaje siempre son obligatorios. Para ocultar toda la sección de
        contacto de la página de inicio, usa <a href="{{ route('admin.section-visibility.edit') }}" class="font-semibold text-sky-700 underline">Visibilidad de secciones</a>.
    </p>

    <h2 class="mb-3 mt-8 font-serif text-lg text-sky-800">Campos existentes</h2>
    <form method="POST" action="{{ route('admin.contact-form.update') }}" class="max-w-lg space-y-3">
        @csrf
        @method('PUT')

        @foreach ($fixedFields as $key => $label)
            <label class="flex items-center justify-between gap-4 rounded-2xl border border-paper-200 bg-paper-alt px-4 py-3">
                <span class="text-sm font-semibold text-ink">{{ $label }}</span>
                <input
                    type="checkbox"
                    name="fields[]"
                    value="{{ $key }}"
                    class="size-5 rounded border-paper-200 accent-sky-600"
                    @checked($visibleFields[$key])
                >
            </label>
        @endforeach

        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>

    <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Campos personalizados</h2>
    <p class="text-sm text-ink-soft">
        Se muestran como campos de texto adicionales, en el orden en que los añadas.
    </p>

    <div class="mt-4 max-w-lg space-y-3">
        @forelse ($customFields as $field)
            <div class="flex items-center justify-between gap-4 rounded-2xl border border-paper-200 bg-paper-alt px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-ink">{{ $field['label'] }}</p>
                    @if ($field['required'])
                        <p class="text-xs text-ink-soft">Obligatorio</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.contact-form.custom-fields.destroy', $field['key']) }}" onsubmit="return confirm('¿Eliminar este campo?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-clay-500 hover:underline">Eliminar</button>
                </form>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                Todavía no hay campos personalizados.
            </p>
        @endforelse
    </div>

    <h3 class="mb-3 mt-6 font-serif text-base text-sky-800">Añadir un campo</h3>
    <form method="POST" action="{{ route('admin.contact-form.custom-fields.store') }}" class="max-w-lg space-y-4">
        @csrf
        <div>
            <label for="label" class="mb-1.5 block text-sm font-semibold text-sky-700">Etiqueta del campo</label>
            <input type="text" name="label" id="label" maxlength="80" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="¿Cómo nos conociste?">
            @error('label')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input type="checkbox" name="required" value="1" class="rounded border-paper-200 accent-sky-600">
            Hacer obligatorio
        </label>
        <button type="submit" class="btn btn-primary">Añadir campo</button>
    </form>
</x-admin-layout>
