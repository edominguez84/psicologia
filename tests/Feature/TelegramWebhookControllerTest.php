<?php

namespace Tests\Feature;

use App\Models\ChatbotConversation;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token'],
            'anthropic' => ['api_key' => 'sk-ant-test', 'model' => 'claude-3-5-haiku-latest'],
        ]);
    }

    private function postWebhook(array $payload, ?string $secret = 'test-token'): \Illuminate\Testing\TestResponse
    {
        return $this->call('POST', '/webhooks/telegram', [], [], [], array_filter([
            'HTTP_X_Telegram_Bot_Api_Secret_Token' => $secret,
            'CONTENT_TYPE' => 'application/json',
        ]), json_encode($payload));
    }

    public function test_responde_un_mensaje_con_ia_y_lo_guarda_en_el_historial(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['text' => 'Las sesiones duran 50 minutos.']]], 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $response = $this->postWebhook([
            'message' => ['chat' => ['id' => 555], 'text' => '¿Cuánto dura la sesión?'],
        ]);

        $response->assertOk();
        $conversation = ChatbotConversation::where('external_chat_id', '555')->first();
        $this->assertNotNull($conversation);
        $this->assertCount(2, $conversation->history);
        $this->assertSame('user', $conversation->history[0]['role']);
        $this->assertSame('assistant', $conversation->history[1]['role']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && ($request['text'] ?? null) === 'Las sesiones duran 50 minutos.');
    }

    public function test_rechaza_un_webhook_con_secret_token_invalido(): void
    {
        $response = $this->postWebhook([
            'message' => ['chat' => ['id' => 555], 'text' => 'Hola'],
        ], secret: 'token-falso');

        $response->assertStatus(401);
        $this->assertDatabaseCount('chatbot_conversations', 0);
    }

    public function test_ignora_una_actualizacion_sin_mensaje_de_texto(): void
    {
        $response = $this->postWebhook(['update_id' => 1]);

        $response->assertOk();
        $this->assertDatabaseCount('chatbot_conversations', 0);
    }

    public function test_avisa_que_el_asistente_no_esta_activo_sin_api_key_de_ia(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token'],
            'anthropic' => ['api_key' => ''],
        ]);
        Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);

        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'Hola']]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'] ?? '', 'todavía no está activo'));
    }

    public function test_mantiene_el_historial_entre_mensajes_de_la_misma_conversacion(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['text' => 'Respuesta']]], 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'Primer mensaje']]);
        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'Segundo mensaje']]);

        $conversation = ChatbotConversation::where('external_chat_id', '555')->first();
        $this->assertCount(4, $conversation->history);
    }
}
