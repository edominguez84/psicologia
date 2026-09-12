<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($method === 'totp')
        <p class="mb-5 text-sm text-ink-soft">
            Abre tu app autenticadora (Google Authenticator, Authy, etc.) e ingresa el código
            de 6 dígitos que muestra para tu cuenta.
        </p>
    @else
        <p class="mb-5 text-sm text-ink-soft">
            Te enviamos un código de 6 dígitos por email. Caduca en 2 horas; si expira, te
            enviaremos uno nuevo automáticamente.
        </p>
    @endif

    <form method="POST" action="{{ route('2fa.verify') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="code" value="Código de 6 dígitos" />
            <x-text-input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                autofocus
                required
                class="text-center font-mono text-lg tracking-[0.5em]"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input type="checkbox" name="remember_device" value="1" class="rounded border-paper-200 accent-sky-600">
            Recordar este dispositivo (no pedir 2FA la próxima vez)
        </label>

        <div class="flex items-center justify-between gap-4">
            @if ($method !== 'totp')
                <form method="POST" action="{{ route('2fa.resend') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-sky-700 underline">
                        Reenviar código
                    </button>
                </form>
            @else
                <span></span>
            @endif

            <x-primary-button>Verificar</x-primary-button>
        </div>
    </form>
</x-guest-layout>
