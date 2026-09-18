<?php

namespace App\Console\Commands;

use App\Models\ChatbotConversation;
use App\Services\SiteSettingsService;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Cierra por inactividad las conversaciones del bot de Telegram: si no ha
 * habido un mensaje nuevo en más de site_settings.chatbot_channels.
 * chat_widget.inactivity_timeout_minutes, envía el mensaje de despedida
 * configurado y marca la conversación como cerrada (closed_at) para no
 * repetir el aviso.
 *
 * Solo aplica a Telegram: el widget web resuelve su propio timeout en el
 * navegador (ver resources/js/components/ChatbotWidget.vue), ya que ahí sí
 * se puede empujar el mensaje de despedida sin depender de un cron —
 * Telegram, en cambio, requiere un push real (TelegramBotService::sendMessage())
 * porque no hay ninguna conexión abierta esperando una respuesta.
 */
class CloseInactiveChatbotConversations extends Command
{
    protected $signature = 'chatbot:close-inactive-conversations';

    protected $description = 'Envía el mensaje de despedida y cierra las conversaciones de Telegram inactivas por más tiempo del configurado';

    public function handle(SiteSettingsService $settings, TelegramBotService $telegram): int
    {
        $chatWidget = $settings->get('chatbot_channels', [])['chat_widget'] ?? [];
        $timeoutMinutes = (int) ($chatWidget['inactivity_timeout_minutes'] ?? 5);
        $farewellMessage = $chatWidget['farewell_message'] ?? 'Veo que no tienes otra consulta, buen día, adiós.';

        if (! $telegram->isEnabled()) {
            return self::SUCCESS;
        }

        $threshold = now()->subMinutes($timeoutMinutes);

        $conversations = ChatbotConversation::where('channel', 'telegram')
            ->inactiveSince($threshold)
            ->get();

        foreach ($conversations as $conversation) {
            try {
                $telegram->sendMessage($conversation->external_chat_id, $farewellMessage);
                $conversation->appendMessage('assistant', $farewellMessage);
            } catch (\Throwable $e) {
                Log::warning("No se pudo enviar la despedida por inactividad a la conversación de Telegram #{$conversation->id}: ".$e->getMessage());
            }

            // Se marca cerrada aunque el envío haya fallado — evita reintentar
            // en cada ejecución del cron contra un chat que quizás ya no
            // existe (bot bloqueado, etc.); si el paciente vuelve a escribir,
            // la conversación se retoma con normalidad (closed_at no bloquea
            // nada, solo evita repetir la despedida).
            $conversation->forceFill(['closed_at' => now()])->save();
        }

        return self::SUCCESS;
    }
}
