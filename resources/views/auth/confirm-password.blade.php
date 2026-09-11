<x-guest-layout>
    <div class="mb-5 text-sm text-ink-soft">
        Esta es una zona segura. Confirma tu contraseña antes de continuar.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <x-primary-button>Confirmar</x-primary-button>
    </form>
</x-guest-layout>
