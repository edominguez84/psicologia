<x-admin-layout title="Seguridad">
    <h1 class="text-2xl font-serif text-sky-800">Seguridad del sitio</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Activa aquí qué canales de 2FA y proveedores de login social están disponibles para que
        cada usuario elija su propio método en "Mi seguridad". Un canal marcado sin credenciales
        de entorno configuradas queda "pendiente" y el sistema sigue usando email de respaldo.
    </p>

    <form method="POST" action="{{ route('admin.security.update') }}" class="mt-8 max-w-lg space-y-8">
        @csrf
        @method('PUT')

        <div>
            <h2 class="mb-3 font-serif text-lg text-sky-800">Canales de 2FA</h2>
            <div class="space-y-2">
                <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                    <input type="checkbox" checked disabled class="rounded border-paper-200 accent-sky-600">
                    Email <span class="text-xs text-ink-soft">(siempre activo)</span>
                </label>
                @foreach (['sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $key => $label)
                    <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                        <input type="checkbox" name="channels[{{ $key }}]" value="1" @checked($armedChannels[$key] ?? false)
                            class="rounded border-paper-200 accent-sky-600">
                        {{ $label }}
                        @if (($armedChannels[$key] ?? false) && ! ($realChannels[$key] ?? false))
                            <span class="rounded-full bg-clay-400/20 px-2 py-0.5 text-xs font-semibold text-clay-500">
                                Pendiente de configuración (falta TWILIO_SID/TWILIO_TOKEN)
                            </span>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="mb-3 font-serif text-lg text-sky-800">Login social</h2>
            <div class="space-y-2">
                @foreach (['google' => 'Google', 'facebook' => 'Facebook', 'microsoft' => 'Microsoft'] as $key => $label)
                    <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                        <input type="checkbox" name="oauth[{{ $key }}]" value="1" @checked($armedOauth[$key] ?? false)
                            class="rounded border-paper-200 accent-sky-600">
                        {{ $label }}
                        @if (($armedOauth[$key] ?? false) && ! ($realOauth[$key] ?? false))
                            <span class="rounded-full bg-clay-400/20 px-2 py-0.5 text-xs font-semibold text-clay-500">
                                Pendiente de configuración (falta {{ strtoupper($key) }}_CLIENT_ID/SECRET)
                            </span>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label for="trusted_device_days" class="mb-1.5 block text-sm font-semibold text-sky-700">
                Días que dura "recordar este dispositivo"
            </label>
            <input type="number" name="trusted_device_days" id="trusted_device_days" min="1" max="365"
                value="{{ old('trusted_device_days', $current['trusted_device_days'] ?? 30) }}"
                class="w-32 rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
        </div>

        <div>
            <label for="inactivity_timeout_minutes" class="mb-1.5 block text-sm font-semibold text-sky-700">
                Cerrar sesión tras minutos de inactividad
            </label>
            <input type="number" name="inactivity_timeout_minutes" id="inactivity_timeout_minutes" min="1" max="240"
                value="{{ old('inactivity_timeout_minutes', $current['inactivity_timeout_minutes'] ?? 30) }}"
                class="w-32 rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            <p class="mt-1 text-xs text-ink-soft">
                Aplica a todas las cuentas (super administradoras, administradoras y pacientes). 30
                segundos antes de cumplirse este tiempo, se avisa con un mensaje para seguir conectado.
            </p>
            @error('inactivity_timeout_minutes')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Guardar ajustes de seguridad</button>
    </form>
</x-admin-layout>
