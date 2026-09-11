<x-admin-layout title="Contacto">
    <h1 class="text-2xl font-serif text-sky-800">Datos de contacto</h1>
    <p class="mt-2 text-sm text-ink-soft">Esta información aparece en el encabezado, el pie de página y los botones de WhatsApp.</p>

    <form method="POST" action="{{ route('admin.contact.update') }}" class="mt-8 max-w-lg space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="whatsapp" class="mb-1.5 block text-sm font-semibold text-sky-700">WhatsApp (solo dígitos, con código de país)</label>
            <input type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $contact['whatsapp']) }}"
                placeholder="50370257845"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('whatsapp')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="whatsapp_show" class="mb-1.5 block text-sm font-semibold text-sky-700">WhatsApp (formato visible)</label>
            <input type="text" name="whatsapp_show" id="whatsapp_show" value="{{ old('whatsapp_show', $contact['whatsapp_show']) }}"
                placeholder="+503 7025 7845"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('whatsapp_show')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-sky-700">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $contact['email']) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('email')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="area" class="mb-1.5 block text-sm font-semibold text-sky-700">Zona de atención</label>
            <input type="text" name="area" id="area" value="{{ old('area', $contact['area']) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('area')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="response" class="mb-1.5 block text-sm font-semibold text-sky-700">Tiempo de respuesta</label>
            <input type="text" name="response" id="response" value="{{ old('response', $contact['response']) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            @error('response')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</x-admin-layout>
