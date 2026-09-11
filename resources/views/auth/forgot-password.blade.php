<x-guest-layout>
    <div class="mb-5 text-sm text-ink-soft">
        ¿Olvidaste tu contraseña? Escribe tu email y te enviaremos un enlace para restablecerla.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-primary-button>Enviar enlace de recuperación</x-primary-button>
    </form>
</x-guest-layout>
