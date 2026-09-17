<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ChatbotConversation;
use App\Services\ChatbotAiService;
use App\Services\TelegramBotService;
use App\Support\FaqLeadCapture;
use App\Support\FaqMatcher;
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
 *
 * Modo dual FAQ/IA (ver App\Support\FaqMatcher y App\Services\ChatbotAiService):
 * si la IA está apagada, sin configurar, o falla en el momento de responder,
 * el bot cae al modo FAQ (coincidencia de preguntas frecuentes) en vez de
 * quedarse sin poder contestar — el paciente siempre recibe algo útil.
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

        $conversation = ChatbotConversation::firstOrCreate(
            ['channel' => 'telegram', 'external_chat_id' => (string) $chatId],
        );

        // isUsable($conversation) también cae a FAQ si esta conversación ya
        // alcanzó el límite diario de mensajes de IA configurado — protege
        // el gasto de la API ante abuso (el modo FAQ no cuesta nada).
        if (! $ai->isUsable($conversation)) {
            $telegram->sendMessage($chatId, $this->faqReply($conversation, $text));

            return response('ok', 200);
        }

        $conversation->appendMessage('user', $text);

        try {
            $reply = $ai->reply(
                $conversation->history,
                ['channel' => 'telegram', 'external_chat_id' => (string) $chatId],
                fn () => $conversation->update(['lead_captured' => true]),
            );
            $conversation->appendMessage('assistant', $reply);
            $conversation->incrementAiMessageCount();
            $telegram->sendMessage($chatId, $reply);
        } catch (\Throwable $e) {
            // El servicio de IA falló en este momento (llave inválida, error
            // de red, límite de la API, etc.) — en vez de dejar al paciente
            // sin respuesta, se cae al modo FAQ para este mensaje puntual
            // (punto 4 del pedido: si el servicio no está disponible, sigue
            // funcionando el chatbot actual).
            Log::error('Fallo generando respuesta de IA para Telegram, usando FAQ como respaldo: '.$e->getMessage());
            $telegram->sendMessage($chatId, $this->faqReply($conversation, $text));
        }

        return response('ok', 200);
    }

    /**
     * Modo FAQ (sin IA): nunca agenda ninguna cita ni llamada — solo
     * responde preguntas frecuentes o, si no hay ninguna coincidencia,
     * captura nombre/correo/teléfono paso a paso (App\Support\FaqLeadCapture)
     * y promete seguimiento posterior. Regla de negocio confirmada
     * explícitamente por el usuario.
     */
    private function faqReply(ChatbotConversation $conversation, string $text): string
    {
        if ($conversation->faq_capture_step !== null) {
            return FaqLeadCapture::handle($conversation, $text);
        }

        $faq = FaqMatcher::match($text);
        if ($faq) {
            return $faq->answer;
        }

        if (! $conversation->lead_captured) {
            return FaqLeadCapture::start($conversation);
        }

        return FaqMatcher::menuText();
    }
}
