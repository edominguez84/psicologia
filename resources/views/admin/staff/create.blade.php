<x-admin-layout title="Nueva cuenta">
    <h1 class="text-2xl font-serif text-sky-800">Crear cuenta de usuario</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Da de alta manualmente una cuenta de administración, edición o paciente. La cuenta queda
        lista para usar de inmediato, sin necesidad de verificar el correo.
    </p>

    <form method="POST" action="{{ route('admin.staff.store') }}" class="mt-8 max-w-lg space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Nombre completo" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-password-strength-field name="password" label="Contraseña" />

        <div>
            <x-input-label for="role" value="Rol" />
            <select name="role" id="role" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </div>

        <button type="submit" class="btn btn-primary">Crear cuenta</button>
    </form>
</x-admin-layout>
