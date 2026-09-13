<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-paper-200 accent-sky-600">
            Recordarme
        </label>

        <div class="flex items-center justify-between gap-4">
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-sky-700 underline" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

            <x-primary-button>Entrar</x-primary-button>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-ink-soft">
        ¿Eres paciente y no tienes cuenta?
        <a class="font-semibold text-sky-700 underline" href="{{ route('register') }}">Regístrate</a>
    </p>

    @php $oauth = app(\App\Services\SecurityAvailability::class)->oauth(); @endphp
    @if (in_array(true, $oauth, true))
        <div class="mt-6 border-t border-paper-200 pt-6">
            <p class="mb-3 text-center text-xs font-semibold uppercase tracking-wider text-ink-soft">O entra con</p>
            <div class="flex flex-col gap-2">
                @foreach (['google' => 'Google', 'facebook' => 'Facebook', 'microsoft' => 'Microsoft'] as $provider => $label)
                    @if ($oauth[$provider])
                        <a
                            href="{{ route('oauth.redirect', $provider) }}"
                            class="btn btn-ghost w-full"
                        >
                            {{ $label }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</x-guest-layout>
