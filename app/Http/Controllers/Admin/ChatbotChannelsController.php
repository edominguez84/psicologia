<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Services\TelegramBotService;
use App\Support\PdfTextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        'anthropic' => [
            // Interruptor manual, independiente de si hay o no api_key —
            // permite apagar la IA sin borrar la llave guardada (punto 5
            // del pedido: activar/desactivar el uso de IA).
            'enabled' => false,
            'api_key' => '',
            'model' => 'claude-haiku-4-5-20251001',
            // 0 = respuestas más consistentes/predecibles, 1 = más
            // creativas/variadas. Anthropic acepta hasta 1.0.
            'temperature' => 0.3,
            // Texto ya extraído del PDF fuente (ver PdfTextExtractor), no el
            // archivo en sí — se recalcula solo cuando se sube un PDF nuevo.
            'pdf_source_path' => null,
            'pdf_source_name' => null,
            'pdf_source_text' => '',
        ],
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
            'anthropic_enabled' => ['nullable', 'boolean'],
            'anthropic_api_key' => ['nullable', 'string', 'max:255'],
            'anthropic_model' => ['nullable', 'string', 'max:100'],
            'anthropic_temperature' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $current = array_replace_recursive(self::DEFAULTS, $this->settings->get('chatbot_channels', []));

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
                'enabled' => $request->boolean('anthropic_enabled'),
                'api_key' => $data['anthropic_api_key'] ?? '',
                'model' => $data['anthropic_model'] ?? self::DEFAULTS['anthropic']['model'],
                // Cast explícito a float: la validación 'numeric' acepta el
                // string "0.3" tal cual llega del <input type="range"> sin
                // convertirlo, y Anthropic rechaza temperature si no es un
                // número JSON real (rechazaba la request completa, cayendo
                // siempre al modo FAQ aunque la API key fuera válida).
                'temperature' => isset($data['anthropic_temperature']) ? (float) $data['anthropic_temperature'] : self::DEFAULTS['anthropic']['temperature'],
                // El PDF fuente se administra con su propio formulario
                // (updatePdfSource/destroyPdfSource) — se conserva tal cual
                // estaba al guardar el resto de esta configuración.
                'pdf_source_path' => $current['anthropic']['pdf_source_path'],
                'pdf_source_name' => $current['anthropic']['pdf_source_name'],
                'pdf_source_text' => $current['anthropic']['pdf_source_text'],
            ],
        ]);

        return back()->with('status', 'Configuración de canales del chatbot actualizada.');
    }

    /**
     * Sube un PDF como fuente de datos adicional para la IA (además de las
     * FAQs) — se extrae su texto una sola vez aquí y se guarda ya
     * procesado, en vez de volver a parsear el archivo en cada mensaje.
     */
    public function updatePdfSource(Request $request): RedirectResponse
    {
        $request->validate([
            'pdf_source' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'pdf_source.mimes' => 'Debe ser un archivo PDF.',
            'pdf_source.max' => 'El PDF no debe superar 10 MB.',
        ]);

        $file = $request->file('pdf_source');
        $path = $file->store('chatbot-sources', 'public');

        try {
            $text = PdfTextExtractor::extract($file->getRealPath());
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);

            return back()->with('status', 'No se pudo leer el PDF: '.$e->getMessage());
        }

        $channels = array_replace_recursive(self::DEFAULTS, $this->settings->get('chatbot_channels', []));

        // Reemplaza el PDF anterior, si había uno.
        if (! empty($channels['anthropic']['pdf_source_path'])) {
            Storage::disk('public')->delete($channels['anthropic']['pdf_source_path']);
        }

        $channels['anthropic']['pdf_source_path'] = $path;
        $channels['anthropic']['pdf_source_name'] = $file->getClientOriginalName();
        $channels['anthropic']['pdf_source_text'] = $text;

        $this->settings->set('chatbot_channels', $channels);

        return back()->with('status', 'PDF cargado: la IA ya lo usa como fuente de información.');
    }

    public function destroyPdfSource(): RedirectResponse
    {
        $channels = array_replace_recursive(self::DEFAULTS, $this->settings->get('chatbot_channels', []));

        if (! empty($channels['anthropic']['pdf_source_path'])) {
            Storage::disk('public')->delete($channels['anthropic']['pdf_source_path']);
        }

        $channels['anthropic']['pdf_source_path'] = null;
        $channels['anthropic']['pdf_source_name'] = null;
        $channels['anthropic']['pdf_source_text'] = '';

        $this->settings->set('chatbot_channels', $channels);

        return back()->with('status', 'Se quitó el PDF fuente.');
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
