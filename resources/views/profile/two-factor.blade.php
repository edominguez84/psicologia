<x-admin-layout title="Mi seguridad">
    <h1 class="text-2xl font-serif text-sky-800">Mi seguridad</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Elige cómo quieres verificar tu identidad al iniciar sesión y administra los
        dispositivos que hayas marcado como de confianza.
    </p>

    <div class="mt-8 max-w-lg">
        <h2 class="mb-3 font-serif text-lg text-sky-800">Método de acceso (2FA)</h2>
        <p class="mb-4 text-sm text-ink-soft">
            Método actual: <strong class="text-sky-700">{{ ucfirst($user->effectiveTwoFactorMethod()) }}</strong>
        </p>

        <form method="PUT" action="{{ route('two-factor.method.update') }}" class="mb-6 space-y-3">
            @csrf
            @method('PUT')
            <div class="flex flex-wrap gap-2">
                @foreach ($channels as $channel => $available)
                    <label
                        class="cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition-colors {{ ! $available ? 'cursor-not-allowed opacity-40' : '' }} {{ $user->effectiveTwoFactorMethod() === $channel ? 'border-sky-600 bg-sky-600 text-paper-50' : 'border-paper-200 bg-paper-50 text-ink-soft hover:border-sky-300' }}"
                    >
                        <input
                            type="radio" name="method" value="{{ $channel }}" class="sr-only"
                            @checked($user->effectiveTwoFactorMethod() === $channel)
                            @disabled(! $available)
                        >
                        {{ ucfirst($channel) }}
                        @unless ($available)
                            <span class="text-xs">(no disponible aún)</span>
                        @endunless
                    </label>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary text-sm">Guardar método</button>
        </form>
        <x-input-error :messages="$errors->get('method')" class="mb-4" />

        <div class="rounded-2xl border border-paper-200 bg-white p-5">
            <h3 class="mb-2 font-serif text-base text-sky-800">Autenticador (TOTP)</h3>
            @if ($user->two_factor_confirmed_at)
                <p class="text-sm text-ink-soft">Tu app autenticadora está activa como método de acceso.</p>
            @elseif ($pendingSecret)
                <p class="mb-3 text-sm text-ink-soft">
                    Escanea este código con Google Authenticator, Authy o similar, o ingresa el
                    secreto manualmente: <code class="rounded bg-paper-100 px-1.5 py-0.5">{{ $pendingSecret }}</code>
                </p>
                <div class="mb-4 inline-block rounded-xl border border-paper-200 bg-white p-3">
                    {!! $qrCodeSvg !!}
                </div>
                <form method="POST" action="{{ route('two-factor.totp.confirm') }}" class="flex items-end gap-3">
                    @csrf
                    <div>
                        <x-input-label for="code" value="Código de la app" />
                        <x-text-input id="code" name="code" type="text" inputmode="numeric" maxlength="6" required class="w-32 text-center font-mono" />
                    </div>
                    <button type="submit" class="btn btn-primary text-sm">Confirmar</button>
                </form>
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            @else
                <p class="mb-3 text-sm text-ink-soft">Aún no has activado un autenticador.</p>
                <form method="POST" action="{{ route('two-factor.totp.setup') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost text-sm">Configurar autenticador</button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-10 max-w-lg">
        <h2 class="mb-3 font-serif text-lg text-sky-800">Dispositivos de confianza</h2>
        @if ($devices->isEmpty())
            <p class="text-sm text-ink-soft">No tienes dispositivos recordados.</p>
        @else
            <div class="space-y-2">
                @foreach ($devices as $device)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-paper-200 bg-white px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm text-ink">{{ $device->user_agent ?: 'Dispositivo desconocido' }}</p>
                            <p class="text-xs text-ink-soft">
                                Último uso: {{ $device->last_used_at?->format('d/m/Y H:i') ?? '—' }} ·
                                Expira: {{ $device->expires_at->format('d/m/Y') }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('two-factor.devices.forget', $device) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="shrink-0 text-xs font-semibold text-clay-500 hover:underline">Olvidar</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
