<x-admin-layout title="Permisos por rol">
    <h1 class="text-2xl font-serif text-sky-800">Permisos por rol</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Elige qué secciones del panel puede ver cada rol. Los roles del sistema no cambian — esto solo
        ajusta qué ve un "Administrador" o un "Editor" dentro del panel. Super administrador siempre ve
        todo, sin excepción.
    </p>

    <form method="POST" action="{{ route('admin.role-permissions.update') }}" class="mt-8 max-w-3xl space-y-8">
        @csrf
        @method('PUT')

        @foreach ($roles as $role)
            <div class="rounded-2xl border border-paper-200 p-5">
                <h2 class="font-serif text-lg text-sky-800">{{ ucfirst($role === 'admin' ? 'Administrador' : 'Editor') }}</h2>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($features as $key => $feature)
                        <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm">
                            <input
                                type="checkbox"
                                name="{{ $role }}[]"
                                value="{{ $key }}"
                                @checked($permissions[$role][$key] ?? true)
                                class="accent-sky-600"
                            >
                            {{ $feature['label'] }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary">Guardar permisos</button>
    </form>
</x-admin-layout>
