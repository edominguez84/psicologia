<x-admin-layout title="Llamadas de confirmación">
    <h1 class="text-2xl font-serif text-sky-800">Llamadas automáticas de confirmación</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Cuando una cita queda aprobada, el sistema puede llamar automáticamente al paciente (con
        una voz de inteligencia artificial vía <a href="https://vapi.ai" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">VAPI</a>)
        para recordarle el horario y pedirle que confirme, con la anticipación que definas abajo.
        Esta llamada es un recordatorio de cortesía — no cambia el estado de la cita, ya aprobada
        por ti.
    </p>

    @if (session('vapi_test_result'))
        @php $vapiTestResult = session('vapi_test_result'); @endphp
        <div class="mt-4 rounded-2xl border px-4 py-3 text-sm font-semibold {{ $vapiTestResult['ok'] ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-clay-200 bg-clay-50 text-clay-700' }}">
            {{ $vapiTestResult['message'] }}
        </div>
    @endif

    <div class="mt-8 max-w-2xl rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-ink">
        <h2 class="mb-2 font-serif text-lg text-sky-800">Pasos para configurar VAPI</h2>
        <ol class="list-decimal space-y-2 pl-5">
            <li>Crea una cuenta en <a href="https://vapi.ai" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">vapi.ai</a> y entra al dashboard.</li>
            <li>En <strong>API Keys</strong>, copia tu <strong>Private Key</strong> — pégala abajo en "API key".</li>
            <li>En <strong>Assistants</strong>, crea un asistente nuevo. En su guion (system prompt / first message),
                usa <code class="rounded bg-paper-100 px-1">&#123;&#123;nombrePaciente&#125;&#125;</code> y
                <code class="rounded bg-paper-100 px-1">&#123;&#123;horarioCita&#125;&#125;</code> donde quieras que mencione el
                nombre del paciente y el horario de su cita — el sistema se los envía automáticamente en cada llamada.
                Pídele que al final de la llamada pregunte si confirma o no la cita.</li>
            <li>Copia el <strong>Assistant ID</strong> (aparece en la URL o en los detalles del asistente) — pégalo
                abajo en "Assistant ID".</li>
            <li>En <strong>Phone Numbers</strong>, importa o compra un número desde el que se harán las llamadas.
                Copia su <strong>Phone Number ID</strong> — pégalo abajo.</li>
            <li>En la configuración del asistente (o de la organización), en <strong>Server URL</strong>, pega esta
                URL: <code class="break-all rounded bg-paper-100 px-1">{{ $webhookUrl }}</code></li>
            <li>En esa misma sección de VAPI, agrega un header personalizado llamado
                <code class="rounded bg-paper-100 px-1">X-Vapi-Secret</code> con este valor exacto:
                <code class="break-all rounded bg-paper-100 px-1">{{ $vapi['webhook_secret'] }}</code>
                (así el sistema verifica que el resultado de la llamada viene realmente de VAPI).</li>
            <li>Guarda todo abajo y activa el interruptor cuando quieras que empiece a llamar.</li>
        </ol>
    </div>

    <form method="POST" action="{{ route('admin.vapi-settings.update') }}" class="mt-8 max-w-xl space-y-6">
        @csrf
        @method('PUT')

        <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
            <input type="checkbox" name="enabled" value="1" @checked($vapi['enabled']) class="accent-sky-600">
            Activar llamadas automáticas de confirmación
        </label>

        <div>
            <label for="api_key" class="mb-1.5 block text-sm font-semibold text-sky-700">API key (Private Key)</label>
            <input type="password" name="api_key" id="api_key" value="{{ old('api_key', $vapi['api_key']) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
        </div>

        <div>
            <label for="assistant_id" class="mb-1.5 block text-sm font-semibold text-sky-700">Assistant ID</label>
            <input type="text" name="assistant_id" id="assistant_id" value="{{ old('assistant_id', $vapi['assistant_id']) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
        </div>

        <div>
            <label for="phone_number_id" class="mb-1.5 block text-sm font-semibold text-sky-700">Phone Number ID</label>
            <input type="text" name="phone_number_id" id="phone_number_id" value="{{ old('phone_number_id', $vapi['phone_number_id']) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
        </div>

        <div>
            <label for="hours_before" class="mb-1.5 block text-sm font-semibold text-sky-700">Horas antes de la cita en que se llama</label>
            <input type="number" name="hours_before" id="hours_before" min="1" max="168"
                value="{{ old('hours_before', $vapi['hours_before']) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            <p class="mt-1 text-xs text-ink-soft">Ej. 24 = se llama un día antes del horario de la cita.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Guardar configuración</button>
            @if (! empty($vapi['api_key']))
                <button type="submit" form="vapi-test-form" class="btn btn-ghost text-sm">Probar conexión</button>
            @endif
        </div>
    </form>

    {{-- Formulario aparte del principal a propósito: HTML no permite un
         <form> anidado dentro de otro (ver el mismo patrón en
         admin/payment-settings/edit.blade.php). --}}
    @if (! empty($vapi['api_key']))
        <form id="vapi-test-form" method="POST" action="{{ route('admin.vapi-settings.test-connection') }}" class="hidden">
            @csrf
        </form>
    @endif

    @if (! empty($vapi['api_key']) && ! empty($vapi['assistant_id']) && ! empty($vapi['phone_number_id']))
        <div class="mt-8 max-w-xl rounded-2xl border border-sky-200 bg-sky-50 p-5">
            <h2 class="mb-2 font-serif text-lg text-sky-800">📞 Llamada de prueba</h2>
            <p class="mb-4 text-sm text-ink-soft">
                Escribe cualquier nombre y teléfono para probar el guion del asistente ahora mismo —
                no necesita corresponder a ninguna cuenta ni cita real del sitio.
            </p>
            <form method="POST" action="{{ route('admin.vapi-settings.send-test-call') }}" class="space-y-4"
                onsubmit="return confirm('Esto va a llamar de verdad al teléfono indicado. ¿Continuar?')">
                @csrf
                <div>
                    <label for="test_name" class="mb-1.5 block text-sm font-semibold text-sky-700">Nombre a mencionar</label>
                    <input type="text" name="test_name" id="test_name" required maxlength="100"
                        value="{{ old('test_name') }}" placeholder="Ej. Emerson Domínguez"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                </div>
                <div>
                    <label for="test_phone" class="mb-1.5 block text-sm font-semibold text-sky-700">Teléfono a llamar</label>
                    <input type="tel" name="test_phone" id="test_phone" required maxlength="30"
                        value="{{ old('test_phone') }}" placeholder="Ej. 61079711 o +50361079711"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                    <p class="mt-1 text-xs text-ink-soft">Sin código de país se asume El Salvador (+503).</p>
                </div>
                <button type="submit" class="btn btn-primary text-sm">Llamar ahora</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.vapi-settings.regenerate-webhook-secret') }}" class="mt-4 max-w-xl">
        @csrf
        <button type="submit" class="text-xs font-semibold text-clay-600 underline"
            onclick="return confirm('¿Regenerar el secreto del webhook? Tendrás que actualizarlo en VAPI.')">
            Regenerar secreto del webhook
        </button>
    </form>
</x-admin-layout>
