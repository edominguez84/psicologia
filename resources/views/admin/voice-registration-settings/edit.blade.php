<x-admin-layout title="Registro por llamada">
    <h1 class="text-2xl font-serif text-sky-800">Registro de paciente por llamada de voz</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Alternativa al formulario de registro: un visitante deja su teléfono en el sitio, el
        sistema le llama automáticamente (con voz de inteligencia artificial vía
        <a href="https://vapi.ai" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">VAPI</a>),
        y un asistente recolecta su nombre y correo por voz para crearle una cuenta de paciente —
        sin llenar ningún formulario. Después recibe un correo con su acceso para elegir horario y
        método de pago en el sitio.
    </p>

    @if (session('voice_registration_test_result'))
        @php $testResult = session('voice_registration_test_result'); @endphp
        <div class="mt-4 rounded-2xl border px-4 py-3 text-sm font-semibold {{ $testResult['ok'] ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-clay-200 bg-clay-50 text-clay-700' }}">
            {{ $testResult['message'] }}
        </div>
    @endif

    @unless ($hasVapiAccount)
        <div class="mt-4 rounded-2xl border border-clay-200 bg-clay-50 px-4 py-3 text-sm text-clay-700">
            Primero configura la cuenta de VAPI (API key y Phone Number ID) en
            <a href="{{ route('admin.vapi-settings.edit') }}" class="font-semibold underline">Llamadas de confirmación</a> —
            el registro por voz usa la misma cuenta, solo necesita un asistente distinto.
        </div>
    @endunless

    <div class="mt-8 max-w-2xl rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-ink">
        <h2 class="mb-2 font-serif text-lg text-sky-800">Pasos para configurar el asistente</h2>
        <ol class="list-decimal space-y-2 pl-5">
            <li>En VAPI, crea un <strong>segundo asistente</strong> (distinto al de confirmación de citas) cuyo
                guion pida nombre, correo y teléfono, deletree el correo de vuelta para confirmarlo, y al
                final invoque una función para crear la cuenta.</li>
            <li>En <strong>Tools</strong> del asistente, agrega una tool tipo <strong>Function</strong> llamada
                exactamente <code class="rounded bg-paper-100 px-1">create_patient_account</code>, con parámetros
                <code class="rounded bg-paper-100 px-1">name</code>, <code class="rounded bg-paper-100 px-1">email</code> y
                <code class="rounded bg-paper-100 px-1">phone_number</code> (los tres tipo string, requeridos).</li>
            <li>En esa tool, en <strong>Server URL</strong>, pega esta URL:
                <code class="break-all rounded bg-paper-100 px-1">{{ $toolCallWebhookUrl }}</code></li>
            <li>Agrega un header personalizado <code class="rounded bg-paper-100 px-1">X-Vapi-Secret</code> con este
                valor exacto: <code class="break-all rounded bg-paper-100 px-1">{{ $voiceRegistration['webhook_secret'] }}</code></li>
            <li>Agrega también la tool <strong>Hang Up</strong> al asistente, para que pueda colgar solo al terminar.</li>
            <li>Copia el <strong>Assistant ID</strong> de este segundo asistente — pégalo abajo.</li>
            <li>Guarda todo abajo y activa el interruptor cuando quieras mostrar la opción a los visitantes.</li>
        </ol>
    </div>

    <form method="POST" action="{{ route('admin.voice-registration-settings.update') }}" class="mt-8 max-w-xl space-y-6">
        @csrf
        @method('PUT')

        <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
            <input type="checkbox" name="enabled" value="1" @checked($voiceRegistration['enabled']) class="accent-sky-600">
            Activar el registro de pacientes por llamada de voz
        </label>

        <div>
            <label for="assistant_id" class="mb-1.5 block text-sm font-semibold text-sky-700">Assistant ID (registro por voz)</label>
            <input type="text" name="assistant_id" id="assistant_id" value="{{ old('assistant_id', $voiceRegistration['assistant_id']) }}"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Guardar configuración</button>
        </div>
    </form>

    @if ($hasVapiAccount && filled($voiceRegistration['assistant_id']))
        <div class="mt-8 max-w-xl rounded-2xl border border-sky-200 bg-sky-50 p-5">
            <h2 class="mb-2 font-serif text-lg text-sky-800">📞 Llamada de prueba</h2>
            <p class="mb-4 text-sm text-ink-soft">
                Escribe cualquier teléfono para probar el guion del asistente de registro ahora mismo.
            </p>
            <form method="POST" action="{{ route('admin.voice-registration-settings.send-test-call') }}" class="space-y-4"
                onsubmit="return confirm('Esto va a llamar de verdad al teléfono indicado. ¿Continuar?')">
                @csrf
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

    <form method="POST" action="{{ route('admin.voice-registration-settings.regenerate-webhook-secret') }}" class="mt-4 max-w-xl">
        @csrf
        <button type="submit" class="text-xs font-semibold text-clay-600 underline"
            onclick="return confirm('¿Regenerar el secreto del webhook? Tendrás que actualizarlo en la tool del asistente en VAPI.')">
            Regenerar secreto del webhook
        </button>
    </form>
</x-admin-layout>
