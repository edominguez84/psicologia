<x-admin-layout title="Canales del chatbot">
    <h1 class="text-2xl font-serif text-sky-800">Canales del chatbot</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Conecta el chatbot a canales de mensajería reales. Telegram y el chat del propio sitio
        responden con preguntas frecuentes por defecto, y pasan a responder con IA (Claude, de
        Anthropic) en cuanto activas el interruptor de IA más abajo y guardas una llave válida.
        WhatsApp Business y Facebook Messenger quedan con la configuración lista para cuando
        tengas esas cuentas de desarrollador.
    </p>

    @if (session('telegram_test_result'))
        @php $telegramTestResult = session('telegram_test_result'); @endphp
        <div class="mt-4 rounded-2xl border px-4 py-3 text-sm font-semibold {{ $telegramTestResult['ok'] ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-clay-200 bg-clay-50 text-clay-700' }}">
            {{ $telegramTestResult['message'] }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.chatbot-channels.update') }}" class="mt-8 max-w-2xl space-y-10" x-data>
        @csrf
        @method('PUT')

        {{-- Telegram --}}
        <div class="rounded-2xl border border-paper-200 p-5">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-serif text-lg text-sky-800">Telegram</h2>
                <span class="rounded-full {{ $channels['anthropic']['enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-paper-200 text-ink-soft' }} px-2.5 py-1 text-xs font-bold">
                    {{ $channels['anthropic']['enabled'] ? 'IA activa' : 'Modo preguntas frecuentes' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-ink-soft">
                Crea un bot con <a href="https://t.me/BotFather" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">@BotFather</a>
                en Telegram y pega aquí el token que te da.
            </p>

            <label class="mt-4 flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                <input type="checkbox" name="telegram_enabled" value="1" @checked($channels['telegram']['enabled']) class="accent-sky-600">
                Activar el bot de Telegram
            </label>

            <div class="mt-4">
                <label for="telegram_bot_token" class="mb-1.5 block text-sm font-semibold text-sky-700">Token del bot</label>
                <x-password-input name="telegram_bot_token" id="telegram_bot_token" value="{{ old('telegram_bot_token', $channels['telegram']['bot_token']) }}" autocomplete="off" />
            </div>

            @if (! empty($channels['telegram']['bot_token']))
                <button type="submit" form="telegram-test-form" class="btn btn-ghost mt-4 text-sm">
                    Probar conexión y activar webhook
                </button>
            @endif
        </div>

        {{-- Inteligencia artificial (Anthropic) --}}
        <div class="rounded-2xl border border-paper-200 p-5">
            <h2 class="font-serif text-lg text-sky-800">Inteligencia artificial (Anthropic / Claude)</h2>
            <p class="mt-1 text-sm text-ink-soft">
                Motor que genera las respuestas del bot de Telegram y del chat en vivo del sitio.
                Mientras esté apagada o sin llave, ambos canales responden con las preguntas
                frecuentes configuradas en <a href="{{ route('admin.chatbot-faqs.index') }}" class="font-semibold text-sky-700 underline">Preguntas del chatbot</a>.
                Crea una llave en
                <a href="https://console.anthropic.com" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">console.anthropic.com</a>.
            </p>

            <label class="mt-4 flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                <input type="checkbox" name="anthropic_enabled" value="1" @checked($channels['anthropic']['enabled']) class="accent-sky-600">
                Usar IA para responder (si está apagado, se usan solo las preguntas frecuentes)
            </label>

            <div class="mt-4">
                <label for="anthropic_api_key" class="mb-1.5 block text-sm font-semibold text-sky-700">API key</label>
                <x-password-input name="anthropic_api_key" id="anthropic_api_key" value="{{ old('anthropic_api_key', $channels['anthropic']['api_key']) }}" autocomplete="off" placeholder="sk-ant-..." />
                <p class="mt-1 text-xs text-ink-soft">Sin esta llave (o con la IA apagada), se responde con preguntas frecuentes.</p>
            </div>

            <div class="mt-4">
                <label for="anthropic_model" class="mb-1.5 block text-sm font-semibold text-sky-700">Modelo</label>
                <select name="anthropic_model" id="anthropic_model"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                    @foreach ($models as $modelId => $modelLabel)
                        <option value="{{ $modelId }}" @selected(old('anthropic_model', $channels['anthropic']['model']) === $modelId)>{{ $modelLabel }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink-soft">Los modelos más económicos (Haiku) son suficientes para responder preguntas del sitio y agendar. Los más avanzados (Opus, Fable, Sonnet) cuestan más por mensaje.</p>
            </div>

            <div class="mt-4">
                <label for="anthropic_temperature" class="mb-1.5 block text-sm font-semibold text-sky-700">
                    Temperatura: <span x-text="$refs.temperatureValue?.value ?? '{{ old('anthropic_temperature', $channels['anthropic']['temperature']) }}'"></span>
                </label>
                <input type="range" name="anthropic_temperature" id="anthropic_temperature" x-ref="temperatureValue"
                    min="0" max="1" step="0.1" value="{{ old('anthropic_temperature', $channels['anthropic']['temperature']) }}"
                    class="w-full accent-sky-600">
                <p class="mt-1 text-xs text-ink-soft">0 = respuestas más consistentes y predecibles. 1 = respuestas más variadas y creativas.</p>
            </div>

            <div class="mt-4">
                <label for="anthropic_daily_message_limit" class="mb-1.5 block text-sm font-semibold text-sky-700">Límite de mensajes de IA por conversación al día</label>
                <input type="number" name="anthropic_daily_message_limit" id="anthropic_daily_message_limit" min="1" max="1000"
                    value="{{ old('anthropic_daily_message_limit', $channels['anthropic']['daily_message_limit']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                <p class="mt-1 text-xs text-ink-soft">Protege el gasto de la API: si un mismo chat supera este número de mensajes en un día, sigue respondiendo con preguntas frecuentes en vez de IA hasta el día siguiente.</p>
            </div>
        </div>

        {{-- Personalidad del bot --}}
        <div class="rounded-2xl border border-paper-200 p-5">
            <h2 class="font-serif text-lg text-sky-800">Personalidad del bot</h2>
            <p class="mt-1 text-sm text-ink-soft">
                Cómo se comporta y presenta el asistente. Las reglas de seguridad del sistema (no
                salirse de temas del sitio, no revelar información interna) siempre se mantienen,
                sin importar lo que escribas aquí.
            </p>

            <div class="mt-4">
                <label for="anthropic_bot_name" class="mb-1.5 block text-sm font-semibold text-sky-700">Nombre del asistente</label>
                <input type="text" name="anthropic_bot_name" id="anthropic_bot_name" maxlength="60"
                    value="{{ old('anthropic_bot_name', $channels['anthropic']['bot_name']) }}" placeholder="Alexa"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            </div>

            <div class="mt-4">
                <label for="anthropic_greeting" class="mb-1.5 block text-sm font-semibold text-sky-700">Saludo inicial</label>
                <textarea name="anthropic_greeting" id="anthropic_greeting" rows="2" maxlength="500"
                    placeholder="¡Hola! Soy Alexa, la asistente de Erika. ¿En qué te puedo ayudar hoy?"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">{{ old('anthropic_greeting', $channels['anthropic']['greeting']) }}</textarea>
                <p class="mt-1 text-xs text-ink-soft">Cómo saluda al iniciar una conversación nueva. Déjalo vacío para que el bot elija un saludo natural por su cuenta.</p>
            </div>

            <div class="mt-4">
                <label for="anthropic_personality" class="mb-1.5 block text-sm font-semibold text-sky-700">Cómo debe comportarse (tono, estilo)</label>
                <textarea name="anthropic_personality" id="anthropic_personality" rows="5" maxlength="2000"
                    placeholder="Ejemplo: Sé amable y cercana, usa un tono relajado. Puedes usar palabras salvadoreñas comunes como 'va pues', 'qué onda' o 'cheque' de vez en cuando, sin exagerar. Muestra empatía genuina y evita sonar robótica."
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">{{ old('anthropic_personality', $channels['anthropic']['personality']) }}</textarea>
                <p class="mt-1 text-xs text-ink-soft">Texto libre: describe el tono (amable, serio, alegre), modismos o expresiones que puede usar, y cualquier otro matiz de cómo quieres que se comporte.</p>
            </div>

            <div class="mt-4">
                <label for="anthropic_data_to_request" class="mb-1.5 block text-sm font-semibold text-sky-700">Qué datos debe solicitar al paciente</label>
                <textarea name="anthropic_data_to_request" id="anthropic_data_to_request" rows="2" maxlength="500"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">{{ old('anthropic_data_to_request', $channels['anthropic']['data_to_request']) }}</textarea>
                <p class="mt-1 text-xs text-ink-soft">Qué le pide al paciente antes de agendar o dar más información (por defecto: nombre, correo y teléfono). El nombre y el correo siempre son obligatorios para guardarlo como cliente potencial.</p>
            </div>
        </div>

        {{-- WhatsApp Business --}}
        <div class="rounded-2xl border border-paper-200 p-5">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-serif text-lg text-sky-800">WhatsApp Business</h2>
                <span class="rounded-full bg-paper-200 px-2.5 py-1 text-xs font-bold text-ink-soft">Solo configuración</span>
            </div>
            <p class="mt-1 text-sm text-ink-soft">
                Credenciales de la API de WhatsApp Business de Meta. Guarda aquí tus datos cuando
                tengas la cuenta creada — el envío/recepción real de mensajes no está conectado
                todavía.
            </p>

            <label class="mt-4 flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                <input type="checkbox" name="whatsapp_enabled" value="1" @checked($channels['whatsapp']['enabled']) class="accent-sky-600">
                Activar (cuando la integración esté lista)
            </label>

            <div class="mt-4 space-y-4">
                <div>
                    <label for="whatsapp_phone_number_id" class="mb-1.5 block text-sm font-semibold text-sky-700">Phone Number ID</label>
                    <input type="text" name="whatsapp_phone_number_id" id="whatsapp_phone_number_id" value="{{ old('whatsapp_phone_number_id', $channels['whatsapp']['phone_number_id']) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
                </div>
                <div>
                    <label for="whatsapp_access_token" class="mb-1.5 block text-sm font-semibold text-sky-700">Access Token</label>
                    <x-password-input name="whatsapp_access_token" id="whatsapp_access_token" value="{{ old('whatsapp_access_token', $channels['whatsapp']['access_token']) }}" autocomplete="off" />
                </div>
                <div>
                    <label for="whatsapp_verify_token" class="mb-1.5 block text-sm font-semibold text-sky-700">Verify Token (para el webhook)</label>
                    <input type="text" name="whatsapp_verify_token" id="whatsapp_verify_token" value="{{ old('whatsapp_verify_token', $channels['whatsapp']['verify_token']) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
                </div>
            </div>
        </div>

        {{-- Facebook Messenger --}}
        <div class="rounded-2xl border border-paper-200 p-5">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-serif text-lg text-sky-800">Facebook Messenger</h2>
                <span class="rounded-full bg-paper-200 px-2.5 py-1 text-xs font-bold text-ink-soft">Solo configuración</span>
            </div>
            <p class="mt-1 text-sm text-ink-soft">
                Credenciales de la página de Facebook conectada a Messenger. Igual que WhatsApp,
                queda lista para activar más adelante.
            </p>

            <label class="mt-4 flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                <input type="checkbox" name="facebook_enabled" value="1" @checked($channels['facebook']['enabled']) class="accent-sky-600">
                Activar (cuando la integración esté lista)
            </label>

            <div class="mt-4 space-y-4">
                <div>
                    <label for="facebook_page_id" class="mb-1.5 block text-sm font-semibold text-sky-700">Page ID</label>
                    <input type="text" name="facebook_page_id" id="facebook_page_id" value="{{ old('facebook_page_id', $channels['facebook']['page_id']) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
                </div>
                <div>
                    <label for="facebook_page_access_token" class="mb-1.5 block text-sm font-semibold text-sky-700">Page Access Token</label>
                    <x-password-input name="facebook_page_access_token" id="facebook_page_access_token" value="{{ old('facebook_page_access_token', $channels['facebook']['page_access_token']) }}" autocomplete="off" />
                </div>
                <div>
                    <label for="facebook_verify_token" class="mb-1.5 block text-sm font-semibold text-sky-700">Verify Token (para el webhook)</label>
                    <input type="text" name="facebook_verify_token" id="facebook_verify_token" value="{{ old('facebook_verify_token', $channels['facebook']['verify_token']) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar configuración</button>
    </form>

    {{-- Documento fuente (PDF) — formulario propio, con enctype de subida de
         archivo, fuera del form principal a propósito (ver nota más abajo). --}}
    <div class="mt-8 max-w-2xl rounded-2xl border border-paper-200 p-5">
        <h2 class="font-serif text-lg text-sky-800">Documento fuente (PDF)</h2>
        <p class="mt-1 text-sm text-ink-soft">
            Sube un PDF (tarifario, folleto de servicios, etc.) y la IA lo usará como información
            adicional, junto con las preguntas frecuentes, al responder.
        </p>

        @if (! empty($channels['anthropic']['pdf_source_name']))
            <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                <span>📄 {{ $channels['anthropic']['pdf_source_name'] }}</span>
                <button type="submit" form="chatbot-pdf-delete-form" class="font-semibold text-clay-600 underline">Quitar</button>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.chatbot-channels.pdf-source.update') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-3">
            @csrf
            <input type="file" name="pdf_source" accept="application/pdf" required
                class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-on-dark hover:file:bg-sky-700">
            <button type="submit" class="btn btn-ghost text-sm">Subir PDF</button>
        </form>
        @error('pdf_source')
            <p class="mt-1 text-xs font-semibold text-clay-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Formularios aparte del principal a propósito: HTML no permite un
         <form> anidado dentro de otro (ver el mismo patrón en
         admin/payment-settings/edit.blade.php). --}}
    @if (! empty($channels['telegram']['bot_token']))
        <form id="telegram-test-form" method="POST" action="{{ route('admin.chatbot-channels.test-telegram') }}" class="hidden">
            @csrf
        </form>
    @endif

    @if (! empty($channels['anthropic']['pdf_source_name']))
        <form id="chatbot-pdf-delete-form" method="POST" action="{{ route('admin.chatbot-channels.pdf-source.destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</x-admin-layout>
