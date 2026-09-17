<?php

namespace App\Support;

use App\Models\ChatbotFaq;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Modo FAQ del chatbot (sin IA) para canales de texto libre como Telegram,
 * donde no hay botones de por medio como en el widget web (ver
 * ChatbotWidget.vue). Se usa cuando la IA está apagada, sin configurar, o
 * falla en el momento (ver TelegramWebhookController) — el bot nunca se
 * queda sin poder responder algo, aunque sea de forma más limitada que con
 * IA real.
 */
class FaqMatcher
{
    /**
     * Busca la FAQ activa cuya pregunta se parezca más al texto recibido
     * (coincidencia simple por palabras compartidas, sin IA) — suficiente
     * para un catálogo corto de preguntas frecuentes, sin necesitar
     * embeddings ni búsqueda difusa real.
     */
    public static function match(string $text): ?ChatbotFaq
    {
        $words = self::words($text);

        if ($words->isEmpty()) {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach (ChatbotFaq::active()->ordered()->get() as $faq) {
            $faqWords = self::words($faq->question);
            $score = $words->intersect($faqWords)->count();

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $faq;
            }
        }

        // Exige al menos una palabra significativa en común — evita que
        // cualquier mensaje corto (p.ej. "hola") calce por casualidad con
        // una FAQ que solo comparte una palabra corta.
        return $bestScore > 0 ? $best : null;
    }

    /**
     * Texto listo para mostrar cuando no hay coincidencia clara: la lista
     * numerada de preguntas disponibles, para que el paciente elija
     * reescribiendo una de ellas.
     */
    public static function menuText(): string
    {
        $faqs = ChatbotFaq::active()->ordered()->get();

        if ($faqs->isEmpty()) {
            return 'Por ahora no tengo preguntas frecuentes cargadas. Escríbenos por WhatsApp y te ayudamos directamente.';
        }

        $list = $faqs->map(fn ($faq, $i) => ($i + 1).". {$faq->question}")->implode("\n");

        return "Puedo ayudarte con estas preguntas frecuentes — escribe una tal cual la ves, o contáctanos por WhatsApp para algo más específico:\n\n{$list}";
    }

    private static function words(string $text): Collection
    {
        $normalized = Str::of($text)->lower()->ascii();

        return collect(preg_split('/[^a-z0-9]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($word) => mb_strlen($word) >= 4);
    }
}
