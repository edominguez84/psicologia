<x-admin-layout title="Crear rol">
    <h1 class="text-2xl font-serif text-sky-800">Crear rol</h1>
    <p class="mt-2 text-sm text-ink-soft">
        El rol queda disponible de inmediato en "Crear cuenta" y al cambiar el rol de un usuario
        existente, con exactamente los permisos que marques aquí.
    </p>

    <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-8 max-w-2xl space-y-6">
        @csrf

        <div>
            <x-input-label for="name" value="Nombre del rol" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus placeholder="Ej. Recepcionista" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <p class="mb-2 text-sm font-semibold text-sky-700">Secciones del panel que puede ver</p>
            @include('admin.roles._feature-checkboxes', ['features' => $features, 'checked' => old('features', [])])
        </div>

        <button type="submit" class="btn btn-primary">Crear rol</button>
    </form>
</x-admin-layout>
