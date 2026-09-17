<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Historial de una conversación del chatbot de IA por canal externo
 * (Telegram, web) — ver App\Services\ChatbotAiService. Cada fila es un
 * interlocutor único (channel + external_chat_id): el historial completo
 * se manda a Claude en cada respuesta para que la conversación tenga
 * contexto de lo ya hablado.
 */
class ChatbotConversation extends Model
{
    protected $fillable = [
        'channel', 'external_chat_id', 'history', 'lead_captured',
        'ai_message_count', 'ai_message_count_date',
    ];

    protected $casts = [
        'history' => 'array',
        'lead_captured' => 'boolean',
        'ai_message_count_date' => 'date',
    ];

    /**
     * Máximo de mensajes que se conservan por conversación — evita que el
     * historial crezca sin límite (costo de tokens en cada llamada a la IA)
     * y sirve de tope simple ante abuso.
     */
    private const MAX_HISTORY = 20;

    public function appendMessage(string $role, string $content): void
    {
        $history = $this->history ?? [];
        $history[] = ['role' => $role, 'content' => $content];

        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, -self::MAX_HISTORY);
        }

        $this->history = $history;
        $this->save();
    }

    /**
     * ¿Esta conversación ya alcanzó el límite diario de mensajes de IA
     * configurado por el super_admin? Protege el gasto de la API de
     * Anthropic ante abuso (alguien mandando cientos de mensajes seguidos
     * desde un mismo chat) — el modo FAQ, que no cuesta nada, no se ve
     * afectado por este límite.
     */
    public function hasReachedDailyAiLimit(int $limit): bool
    {
        if (! $this->ai_message_count_date?->isToday()) {
            return false;
        }

        return $this->ai_message_count >= $limit;
    }

    /**
     * Suma un mensaje de IA al contador del día actual — si el contador
     * guardado es de un día anterior, se reinicia en vez de acumularse
     * indefinidamente (sin necesidad de un cron que lo resetee).
     */
    public function incrementAiMessageCount(): void
    {
        if (! $this->ai_message_count_date?->isToday()) {
            $this->ai_message_count = 0;
            $this->ai_message_count_date = Carbon::today();
        }

        $this->ai_message_count++;
        $this->save();
    }
}
