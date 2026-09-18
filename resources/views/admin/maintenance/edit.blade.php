<x-admin-layout title="Modo mantenimiento">
    <h1 class="text-2xl font-serif text-sky-800">Modo mantenimiento</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Mientras esté activado, cualquier visitante que entre al sitio verá una página de
        "en mantenimiento" con los canales de contacto ya configurados (WhatsApp, correo y redes
        sociales) en vez de la landing normal. El panel de administración sigue funcionando con
        normalidad — puedes seguir trabajando y desactivarlo cuando corresponda.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.maintenance.update') }}" class="mt-8 max-w-xl">
        @csrf
        @method('PUT')

        <label class="flex items-start gap-3 rounded-2xl border {{ $enabled ? 'border-clay-300 bg-clay-50' : 'border-paper-200 bg-paper-50' }} px-5 py-4 text-sm">
            <input type="checkbox" name="enabled" value="1" @checked($enabled) class="mt-0.5 accent-clay-500">
            <span>
                <span class="block font-semibold {{ $enabled ? 'text-clay-700' : 'text-ink' }}">
                    Activar el modo mantenimiento
                </span>
                <span class="mt-1 block text-xs {{ $enabled ? 'text-clay-600' : 'text-ink-soft' }}">
                    ⚠️ Al activarlo, el sitio público deja de mostrarse a los visitantes de inmediato.
                </span>
            </span>
        </label>

        <button type="submit" class="btn btn-primary mt-4">Guardar</button>
    </form>
</x-admin-layout>
