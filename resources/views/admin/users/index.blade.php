<x-admin-layout title="Usuarios">
    <h1 class="text-2xl font-serif text-sky-800">Usuarios</h1>
    <p class="mt-2 text-sm text-ink-soft">Gestiona roles y suspende cuentas que no deberían tener acceso.</p>

    <div class="mt-8 overflow-x-auto rounded-2xl border border-paper-200 bg-white">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-paper-200 text-xs font-bold uppercase tracking-wider text-sky-500">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Rol</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-paper-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3 align-top font-semibold text-ink">{{ $user->name }}</td>
                        <td class="px-4 py-3 align-top text-ink-soft">{{ $user->email }}</td>
                        <td class="px-4 py-3 align-top">
                            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="role" onchange="this.form.submit()"
                                    class="rounded-lg border border-paper-200 bg-paper-50 px-2 py-1.5 text-xs">
                                    @foreach (\App\Enums\UserRole::cases() as $role)
                                        <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-3 align-top">
                            @if ($user->isBanned())
                                <span class="rounded-full bg-paper-100 px-2.5 py-1 text-xs font-semibold text-clay-500" title="{{ $user->banned_reason }}">
                                    Suspendida
                                </span>
                            @else
                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Activo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            @if ($user->isBanned())
                                <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-semibold text-sky-700 hover:underline">Reactivar</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.ban', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-semibold text-clay-500 hover:underline">Banear</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
