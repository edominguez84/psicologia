<x-admin-layout title="Canales del chatbot">
    <h1 class="text-2xl font-serif text-sky-800">Canales del chatbot</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Conecta el chatbot a canales de mensajería reales. Telegram ya tiene integración completa
        con IA (Claude, de Anthropic) para responder dudas de pacientes en lenguaje natural.
        WhatsApp Business y Facebook Messenger quedan con la configuración lista para cuando
        tengas esas cuentas de desarrollador.
    </p>

    @if (session('telegram_test_result'))
        @php $telegramTestResult = session('telegram_test_result'); @endphp
        <div class="mt-4 rounded-2xl border px-4 py-3 text-sm font-semibold {{ $telegramTestResult['ok'] ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-clay-200 bg-clay-50 text-clay-700' }}">
            {{ $telegramTestResult['message'] }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.chatbot-channels.update') }}" class="mt-8 max-w-2xl space-y-10">
        @csrf
        @method('PUT')

        {{-- Telegram --}}
        <div class="rounded-2xl border border-paper-200 p-5">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-serif text-lg text-sky-800">Telegram</h2>
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">IA activa</span>
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
                <input type="password" name="telegram_bot_token" id="telegram_bot_token" value="{{ old('telegram_bot_token', $channels['telegram']['bot_token']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
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
                Motor que genera las respuestas del bot de Telegram. Crea una llave en
                <a href="https://console.anthropic.com" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">console.anthropic.com</a>.
            </p>

            <div class="mt-4">
                <label for="anthropic_api_key" class="mb-1.5 block text-sm font-semibold text-sky-700">API key</label>
                <input type="password" name="anthropic_api_key" id="anthropic_api_key" value="{{ old('anthropic_api_key', $channels['anthropic']['api_key']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off" placeholder="sk-ant-...">
                <p class="mt-1 text-xs text-ink-soft">Sin esta llave, el bot de Telegram responderá que el asistente todavía no está activo.</p>
            </div>

            <div class="mt-4">
                <label for="anthropic_model" class="mb-1.5 block text-sm font-semibold text-sky-700">Modelo</label>
                <input type="text" name="anthropic_model" id="anthropic_model" value="{{ old('anthropic_model', $channels['anthropic']['model']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
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
                    <input type="password" name="whatsapp_access_token" id="whatsapp_access_token" value="{{ old('whatsapp_access_token', $channels['whatsapp']['access_token']) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
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
                    <input type="password" name="facebook_page_access_token" id="facebook_page_access_token" value="{{ old('facebook_page_access_token', $channels['facebook']['page_access_token']) }}"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
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

    {{-- Formulario aparte del principal a propósito: HTML no permite un
         <form> anidado dentro de otro (ver el mismo patrón en
         admin/payment-settings/edit.blade.php). --}}
    @if (! empty($channels['telegram']['bot_token']))
        <form id="telegram-test-form" method="POST" action="{{ route('admin.chatbot-channels.test-telegram') }}" class="hidden">
            @csrf
        </form>
    @endif
</x-admin-layout>
