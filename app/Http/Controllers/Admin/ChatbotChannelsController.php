<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Services\TelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuración de credenciales de los canales de mensajería del chatbot.
 * Mismo patrón que Admin\PaymentSettingsController (Wompi): guarda todo en
 * site_settings bajo una sola clave, con un botón de "probar conexión" para
 * el canal que ya tiene integración real (Telegram) — WhatsApp Business y
 * Facebook Messenger quedan con la pantalla de credenciales lista, pero sin
 * lógica de envío/webhook todavía (fuera de alcance hasta que haya cuentas
 * reales de esos proveedores).
 */
class ChatbotChannelsController extends Controller
{
    private const DEFAULTS = [
        'telegram' => ['enabled' => false, 'bot_token' => ''],
        'whatsapp' => ['enabled' => false, 'phone_number_id' => '', 'access_token' => '', 'verify_token' => ''],
        'facebook' => ['enabled' => false, 'page_id' => '', 'page_access_token' => '', 'verify_token' => ''],
        'anthropic' => ['api_key' => '', 'model' => 'claude-3-5-haiku-latest'],
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $channels = array_replace_recursive(self::DEFAULTS, $this->settings->get('chatbot_channels', []));

        return view('admin.chatbot-channels.edit', [
            'channels' => $channels,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'telegram_enabled' => ['nullable', 'boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:255'],
            'whatsapp_access_token' => ['nullable', 'string', 'max:1000'],
            'whatsapp_verify_token' => ['nullable', 'string', 'max:255'],
            'facebook_enabled' => ['nullable', 'boolean'],
            'facebook_page_id' => ['nullable', 'string', 'max:255'],
            'facebook_page_access_token' => ['nullable', 'string', 'max:1000'],
            'facebook_verify_token' => ['nullable', 'string', 'max:255'],
            'anthropic_api_key' => ['nullable', 'string', 'max:255'],
            'anthropic_model' => ['nullable', 'string', 'max:100'],
        ]);

        $this->settings->set('chatbot_channels', [
            'telegram' => [
                'enabled' => $request->boolean('telegram_enabled'),
                'bot_token' => $data['telegram_bot_token'] ?? '',
            ],
            'whatsapp' => [
                'enabled' => $request->boolean('whatsapp_enabled'),
                'phone_number_id' => $data['whatsapp_phone_number_id'] ?? '',
                'access_token' => $data['whatsapp_access_token'] ?? '',
                'verify_token' => $data['whatsapp_verify_token'] ?? '',
            ],
            'facebook' => [
                'enabled' => $request->boolean('facebook_enabled'),
                'page_id' => $data['facebook_page_id'] ?? '',
                'page_access_token' => $data['facebook_page_access_token'] ?? '',
                'verify_token' => $data['facebook_verify_token'] ?? '',
            ],
            'anthropic' => [
                'api_key' => $data['anthropic_api_key'] ?? '',
                'model' => $data['anthropic_model'] ?? self::DEFAULTS['anthropic']['model'],
            ],
        ]);

        return back()->with('status', 'Configuración de canales del chatbot actualizada.');
    }

    /**
     * Registra el webhook de Telegram apuntando a nuestra URL pública y
     * confirma que el bot_token es válido — feedback inmediato antes de que
     * un paciente real intente escribirle al bot.
     */
    public function testTelegram(TelegramBotService $telegram): RedirectResponse
    {
        try {
            $me = $telegram->getMe();
            $telegram->setWebhook(route('webhooks.telegram'));

            return back()->with('telegram_test_result', [
                'ok' => true,
                'message' => "Conexión exitosa: bot @{$me['username']} conectado y webhook registrado.",
            ]);
        } catch (\Throwable $e) {
            return back()->with('telegram_test_result', [
                'ok' => false,
                'message' => 'No se pudo conectar con Telegram: '.$e->getMessage(),
            ]);
        }
    }
}
