<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ChatbotConversation;
use App\Services\ChatbotAiService;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe cada mensaje nuevo que un paciente le manda al bot de Telegram (ver
 * core.telegram.org/bots/api#update). Ruta pública, sin CSRF (excepción en
 * bootstrap/app.php) — Telegram no manda cookies de sesión.
 *
 * Autenticidad: no se valida firma criptográfica (Telegram no la ofrece por
 * defecto salvo configurar un "secret token" al registrar el webhook); en su
 * lugar se compara el header 'X-Telegram-Bot-Api-Secret-Token' contra el
 * bot_token propio como secreto compartido simple — suficiente para
 * descartar tráfico que no viene de nuestra propia configuración de webhook.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotService $telegram,
        ChatbotAiService $ai,
    ): Response {
        if (! $telegram->isEnabled()) {
            return response('canal deshabilitado', 200);
        }

        if (! $telegram->verifySecretToken($request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            Log::warning('Webhook de Telegram con secret_token inválido, ignorado.', ['ip' => $request->ip()]);

            return response('firma inválida', 401);
        }

        $message = $request->input('message');
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? null;

        if (! $chatId || ! $text) {
            Log::info('Webhook de Telegram sin mensaje de texto procesable, ignorado.');

            return response('ok', 200);
        }

        if (! $ai->isConfigured()) {
            $telegram->sendMessage($chatId, 'Este asistente todavía no está activo. Escríbenos por WhatsApp mientras tanto.');

            return response('ok', 200);
        }

        $conversation = ChatbotConversation::firstOrCreate(
            ['channel' => 'telegram', 'external_chat_id' => (string) $chatId],
        );

        $conversation->appendMessage('user', $text);

        try {
            $reply = $ai->reply($conversation->history);
            $conversation->appendMessage('assistant', $reply);
            $telegram->sendMessage($chatId, $reply);
        } catch (\Throwable $e) {
            Log::error('Fallo generando respuesta de IA para Telegram: '.$e->getMessage());
            $telegram->sendMessage($chatId, 'Disculpa, tuve un problema respondiendo. Intenta de nuevo en un momento o escríbenos por WhatsApp.');
        }

        return response('ok', 200);
    }
}
