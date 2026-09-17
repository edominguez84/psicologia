<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatbotMessageRequest;
use App\Models\ChatbotConversation;
use App\Services\ChatbotAiService;
use App\Support\FaqLeadCapture;
use App\Support\FaqMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Chat en vivo con IA del widget web (ver resources/js/components/ChatbotWidget.vue).
 * Mismo motor que el bot de Telegram (ChatbotAiService, con las mismas
 * tools de horarios y captura de lead) — el visitante se identifica por su
 * sesión de navegador (cookie de sesión de Laravel), no por un chat_id
 * externo. Si la IA no está usable o falla, cae al modo FAQ, igual que
 * Telegram.
 */
class ChatbotMessageController extends Controller
{
    public function store(StoreChatbotMessageRequest $request, ChatbotAiService $ai): JsonResponse
    {
        $text = $request->string('message')->toString();
        $sessionId = $request->session()->getId();

        $conversation = ChatbotConversation::firstOrCreate(
            ['channel' => 'web', 'external_chat_id' => $sessionId],
        );

        // isUsable($conversation) también cae a FAQ si esta conversación ya
        // alcanzó el límite diario de mensajes de IA configurado — protege
        // el gasto de la API ante abuso (el modo FAQ no cuesta nada).
        if (! $ai->isUsable($conversation)) {
            return response()->json(['mode' => 'faq', 'reply' => $this->faqReply($conversation, $text)]);
        }

        $conversation->appendMessage('user', $text);

        try {
            $reply = $ai->reply(
                $conversation->history,
                ['channel' => 'web', 'external_chat_id' => $sessionId],
                fn () => $conversation->update(['lead_captured' => true]),
            );
            $conversation->appendMessage('assistant', $reply);
            $conversation->incrementAiMessageCount();

            return response()->json(['mode' => 'ai', 'reply' => $reply]);
        } catch (\Throwable $e) {
            Log::error('Fallo generando respuesta de IA para el widget web, usando FAQ como respaldo: '.$e->getMessage());

            return response()->json(['mode' => 'faq', 'reply' => $this->faqReply($conversation, $text)]);
        }
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
