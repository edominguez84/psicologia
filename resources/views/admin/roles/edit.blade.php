<x-admin-layout title="Editar rol">
    <h1 class="text-2xl font-serif text-sky-800">Editar rol: {{ $role->name }}</h1>
    @if ($role->is_system)
        <p class="mt-2 text-sm text-ink-soft">
            Este es uno de los roles del sistema — puedes ajustar sus permisos y su nombre visible,
            pero no eliminarlo.
        </p>
    @endif

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="mt-8 max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="name" value="Nombre del rol" />
            <x-text-input id="name" type="text" name="name" :value="old('name', $role->name)" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <p class="mb-2 text-sm font-semibold text-sky-700">Secciones del panel que puede ver</p>
            @include('admin.roles._feature-checkboxes', ['features' => $features, 'checked' => old('features', array_keys(array_filter($role->permissionsOrDefault())))])
        </div>

        <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </form>
</x-admin-layout>
