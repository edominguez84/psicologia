<x-admin-layout title="Roles">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-serif text-sky-800">Roles</h1>
            <p class="mt-2 text-sm text-ink-soft">
                Los roles marcados "Del sistema" no se pueden eliminar. Crea roles nuevos para el
                personal con exactamente los permisos que necesiten.
            </p>
        </div>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">Crear rol</a>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-8 space-y-3">
        @foreach ($roles as $role)
            <div class="flex items-center justify-between gap-4 rounded-2xl border border-paper-200 bg-white p-4">
                <div>
                    <p class="font-semibold text-ink">
                        {{ $role->name }}
                        @if ($role->is_system)
                            <span class="ml-2 rounded-full bg-paper-200 px-2 py-0.5 text-xs font-bold text-ink-soft">Del sistema</span>
                        @endif
                        @if (! $role->is_staff)
                            <span class="ml-2 rounded-full bg-paper-100 px-2 py-0.5 text-xs font-bold text-ink-soft">Sin acceso al panel</span>
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-ink-soft">
                        {{ $role->slug }} · {{ $role->users_count }} {{ $role->users_count === 1 ? 'cuenta' : 'cuentas' }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-3 text-xs">
                    @if ($role->is_staff && $role->slug !== 'super_admin')
                        <a href="{{ route('admin.roles.edit', $role) }}" class="font-semibold text-sky-700 hover:underline">Permisos</a>
                    @endif
                    @unless ($role->is_system)
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('¿Eliminar el rol {{ $role->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold text-clay-500 hover:underline">Eliminar</button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
</x-admin-layout>
