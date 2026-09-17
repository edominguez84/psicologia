<?php

namespace App\Support;

/**
 * Catálogo de modelos de Anthropic ofrecidos en el selector de
 * /admin/chatbot-channels. Lista estática (no se consulta GET /v1/models
 * en vivo en cada carga del panel — evita depender de la API/latencia solo
 * para dibujar un <select>) confirmada contra la cuenta real del sitio al
 * momento de escribir esto. Si Anthropic libera modelos nuevos, esta lista
 * se actualiza a mano; un modelo ya guardado que ya no aparezca aquí sigue
 * funcionando igual (el <select> simplemente no lo tendría preseleccionado
 * por nombre "bonito", ver ChatbotChannelsController).
 */
class AnthropicModels
{
    public const DEFAULT_MODEL = 'claude-haiku-4-5-20251001';

    public static function all(): array
    {
        return [
            'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 — el más económico y rápido (recomendado)',
            'claude-sonnet-4-5-20250929' => 'Claude Sonnet 4.5',
            'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
            'claude-opus-4-5-20251101' => 'Claude Opus 4.5',
            'claude-opus-4-6' => 'Claude Opus 4.6',
            'claude-opus-4-7' => 'Claude Opus 4.7',
            'claude-opus-4-8' => 'Claude Opus 4.8',
            'claude-sonnet-5' => 'Claude Sonnet 5',
            'claude-opus-5' => 'Claude Opus 5',
            'claude-fable-5' => 'Claude Fable 5',
            'claude-fable-5-1' => 'Claude Fable 5.1',
        ];
    }
}
