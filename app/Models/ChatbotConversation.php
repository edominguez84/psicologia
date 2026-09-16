<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Historial de una conversación del chatbot de IA por canal externo
 * (Telegram hoy). Cada fila es un interlocutor único (channel +
 * external_chat_id) — el historial completo se manda a Claude en cada
 * respuesta para que la conversación tenga contexto de lo ya hablado.
 */
class ChatbotConversation extends Model
{
    protected $fillable = ['channel', 'external_chat_id', 'history'];

    protected $casts = [
        'history' => 'array',
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
}
