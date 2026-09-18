<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integración con la API de Bots de Telegram (core.telegram.org/bots/api) —
 * HTTP REST puro, sin SDK: cada método de la API es un endpoint
 * https://api.telegram.org/bot{token}/{método}. El bot_token se lee siempre
 * de site_settings.chatbot_channels.telegram, nunca de config/env, igual que
 * las credenciales de Wompi.
 */
class TelegramBotService
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function isEnabled(): bool
    {
        $telegram = $this->credentials();

        return $telegram['enabled'] && filled($telegram['bot_token']);
    }

    /**
     * Compara el header 'X-Telegram-Bot-Api-Secret-Token' del webhook
     * entrante contra el webhook_secret propio (ver setWebhook()) — descarta
     * tráfico que no viene de nuestra configuración real.
     */
    public function verifySecretToken(?string $received): bool
    {
        $secret = $this->credentials()['webhook_secret'];

        if (! $received || ! filled($secret)) {
            return false;
        }

        return hash_equals($secret, $received);
    }

    /**
     * Confirma que el bot_token es válido y devuelve la identidad del bot
     * (username, etc.) — usado por el botón "Probar conexión".
     */
    public function getMe(): array
    {
        $response = $this->call('getMe');

        return $response['result'];
    }

    /**
     * Registra la URL pública que Telegram debe llamar con cada mensaje
     * nuevo — se vuelve a llamar cada vez que se guarda/prueba la
     * configuración, así que un cambio de dominio no requiere un paso manual
     * aparte. secret_token viaja en el header 'X-Telegram-Bot-Api-Secret-Token'
     * de cada webhook entrante (ver TelegramWebhookController).
     *
     * Importante: Telegram exige que secret_token solo contenga letras,
     * números, guiones y guiones bajos (rechaza la solicitud completa con
     * "secret token contains illegal characters" si no) — por eso NO se
     * puede reusar el bot_token tal cual (viene con un ':' en medio,
     * formato "123456:AAxxxx", carácter no permitido aquí). Se usa en su
     * lugar un webhook_secret propio, autogenerado con Str::random() (mismo
     * patrón que VapiCallService), guardado junto al bot_token.
     */
    public function setWebhook(string $url): void
    {
        $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $this->credentials()['webhook_secret'],
        ]);
    }

    /**
     * Envía un mensaje de texto plano a un chat de Telegram (paciente que ya
     * le escribió al bot, identificado por su chat_id).
     */
    public function sendMessage(int|string $chatId, string $text): void
    {
        $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    private function call(string $method, array $params = []): array
    {
        $telegram = $this->credentials();

        if (! filled($telegram['bot_token'])) {
            throw new RuntimeException('Telegram no tiene un bot_token configurado.');
        }

        $response = Http::asForm()->post("https://api.telegram.org/bot{$telegram['bot_token']}/{$method}", $params);

        $body = $response->json();

        if ($response->failed() || ! ($body['ok'] ?? false)) {
            throw new RuntimeException($body['description'] ?? 'Telegram rechazó la solicitud.');
        }

        return $body;
    }

    private function credentials(): array
    {
        $channels = $this->settings->get('chatbot_channels', []);

        return array_replace(
            ['enabled' => false, 'bot_token' => '', 'webhook_secret' => ''],
            $channels['telegram'] ?? []
        );
    }
}
