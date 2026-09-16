<?php

namespace App\Services;

use App\Models\ChatbotFaq;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Genera respuestas conversacionales reales (lenguaje natural, no solo
 * botones de FAQ) vía la API de Mensajes de Anthropic (Claude). El system
 * prompt se arma con la información real del sitio (nombre, servicios,
 * FAQs ya configuradas desde /admin/chatbot-faqs) para que el bot responda
 * de forma consistente con lo que la propia psicóloga ya publicó, en vez de
 * inventar información sobre el negocio.
 */
class ChatbotAiService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function isConfigured(): bool
    {
        return filled($this->credentials()['api_key']);
    }

    /**
     * $history es el historial de la conversación en el formato de la API
     * de Anthropic: [{role: 'user'|'assistant', content: '...'}, ...],
     * terminando siempre en el mensaje del paciente que se quiere responder.
     */
    public function reply(array $history): string
    {
        $credentials = $this->credentials();

        if (! filled($credentials['api_key'])) {
            throw new RuntimeException('No hay una llave de Anthropic configurada.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $credentials['api_key'],
            'anthropic-version' => self::API_VERSION,
        ])->post(self::API_URL, [
            'model' => $credentials['model'],
            'max_tokens' => 500,
            'system' => $this->systemPrompt(),
            'messages' => $history,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Anthropic rechazó la solicitud: '.$response->body());
        }

        return $response->json('content.0.text', 'Disculpa, no pude generar una respuesta en este momento.');
    }

    /**
     * Confirma que la API key es válida con la llamada más barata posible
     * (1 token de salida) — usado por el botón "Probar conexión" del panel,
     * sin gastar en una respuesta completa.
     */
    public function testConnection(): void
    {
        $credentials = $this->credentials();

        $response = Http::withHeaders([
            'x-api-key' => $credentials['api_key'],
            'anthropic-version' => self::API_VERSION,
        ])->post(self::API_URL, [
            'model' => $credentials['model'],
            'max_tokens' => 1,
            'messages' => [['role' => 'user', 'content' => 'Hola']],
        ]);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'Anthropic rechazó las credenciales.');
        }
    }

    private function systemPrompt(): string
    {
        $siteName = config('site.name');
        $faqs = ChatbotFaq::active()->ordered()->get(['question', 'answer'])
            ->map(fn ($faq) => "P: {$faq->question}\nR: {$faq->answer}")
            ->implode("\n\n");

        return <<<PROMPT
            Eres el asistente virtual de {$siteName}, un sitio de terapia psicológica online.
            Respondes dudas de pacientes actuales o potenciales de forma breve, cálida y profesional,
            en español, en 2-4 frases como máximo.

            Información ya publicada por la psicóloga que debes usar como base (no inventes datos
            distintos a estos sobre precios, duración de sesiones o el proceso):

            {$faqs}

            Si te preguntan algo médico/clínico específico de su caso (diagnóstico, medicación,
            urgencias), no lo respondas — indica amablemente que eso se conversa directamente en una
            sesión y sugiere agendar una cita o escribir por WhatsApp para casos urgentes.
            Si no sabes la respuesta con la información dada, dilo con honestidad y sugiere escribir
            por WhatsApp para una respuesta más precisa.
            PROMPT;
    }

    private function credentials(): array
    {
        $channels = $this->settings->get('chatbot_channels', []);

        return array_replace(
            ['api_key' => '', 'model' => 'claude-3-5-haiku-latest'],
            $channels['anthropic'] ?? []
        );
    }
}
