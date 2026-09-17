<?php

namespace Tests\Feature;

use App\Models\ChatbotConversation;
use App\Models\ChatbotFaq;
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
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'model' => 'claude-haiku-4-5-20251001'],
        ]);
    }

    private function postWebhook(array $payload, ?string $secret = 'test-token'): \Illuminate\Testing\TestResponse
    {
        return $this->call('POST', '/webhooks/telegram', [], [], [], array_filter([
            'HTTP_X_Telegram_Bot_Api_Secret_Token' => $secret,
            'CONTENT_TYPE' => 'application/json',
        ]), json_encode($payload));
    }

    private function fakeAiReply(string $text): array
    {
        return [
            'content' => [['type' => 'text', 'text' => $text]],
            'stop_reason' => 'end_turn',
        ];
    }

    public function test_responde_un_mensaje_con_ia_y_lo_guarda_en_el_historial(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response($this->fakeAiReply('Las sesiones duran 50 minutos.'), 200),
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

    public function test_sin_api_key_responde_con_faq_en_vez_de_ia(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token'],
            'anthropic' => ['enabled' => false, 'api_key' => ''],
        ]);
        ChatbotFaq::create(['question' => '¿Cuánto dura una sesión?', 'answer' => 'Las sesiones duran 50 minutos.', 'is_active' => true]);
        Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);

        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => '¿Cuánto dura una sesión?']]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'] ?? '', 'Las sesiones duran 50 minutos.'));
    }

    public function test_si_la_ia_esta_apagada_manualmente_responde_con_faq_aunque_haya_api_key(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token'],
            'anthropic' => ['enabled' => false, 'api_key' => 'sk-ant-test'],
        ]);
        Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);

        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'Hola']]);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.anthropic.com'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }

    public function test_si_la_ia_falla_en_el_momento_cae_a_faq_en_vez_de_dejar_sin_respuesta(): void
    {
        ChatbotFaq::create(['question' => '¿Cuánto dura una sesión?', 'answer' => 'Las sesiones duran 50 minutos.', 'is_active' => true]);
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => ['message' => 'rate_limited']], 429),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => '¿Cuánto dura una sesión?']]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'] ?? '', 'Las sesiones duran 50 minutos.'));
    }

    public function test_mantiene_el_historial_entre_mensajes_de_la_misma_conversacion(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response($this->fakeAiReply('Respuesta'), 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'Primer mensaje']]);
        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'Segundo mensaje']]);

        $conversation = ChatbotConversation::where('external_chat_id', '555')->first();
        $this->assertCount(4, $conversation->history);
    }

    public function test_al_superar_el_limite_diario_de_mensajes_de_ia_cae_a_faq(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token'],
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'daily_message_limit' => 1],
        ]);
        ChatbotFaq::create(['question' => '¿Ofrecen descuentos por paquete de sesiones?', 'answer' => 'Sí, con paquete.', 'is_active' => true]);
        Http::fake([
            'api.anthropic.com/*' => Http::response($this->fakeAiReply('Respuesta de IA'), 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        // Primer mensaje: dentro del límite, responde con IA.
        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'Hola']]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && ($request['text'] ?? null) === 'Respuesta de IA');

        // Segundo mensaje: ya alcanzó el límite diario, cae a FAQ sin
        // siquiera llamar a Anthropic.
        Http::fake([
            'api.anthropic.com/*' => Http::response($this->fakeAiReply('Esto no debería usarse'), 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);
        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => '¿Ofrecen descuentos por paquete de sesiones?']]);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.anthropic.com'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'] ?? '', 'Sí, con paquete.'));
    }

    public function test_en_modo_faq_sin_coincidencia_arranca_la_captura_de_datos_en_vez_del_menu(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token'],
            'anthropic' => ['enabled' => false, 'api_key' => ''],
        ]);
        Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);

        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'algo que no coincide con ninguna faq']]);

        $conversation = ChatbotConversation::where('external_chat_id', '555')->first();
        $this->assertSame('name', $conversation->faq_capture_step);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'] ?? '', 'nombre completo'));
    }

    public function test_en_modo_faq_completa_la_captura_de_datos_a_lo_largo_de_varios_mensajes(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token'],
            'anthropic' => ['enabled' => false, 'api_key' => ''],
        ]);
        Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);

        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'no coincide con nada']]);
        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'Elian Domínguez']]);
        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => 'elian@example.com']]);
        $this->postWebhook(['message' => ['chat' => ['id' => 555], 'text' => '77778888']]);

        $this->assertDatabaseHas('chatbot_leads', ['name' => 'Elian Domínguez', 'email' => 'elian@example.com', 'phone' => '77778888']);
        $conversation = ChatbotConversation::where('external_chat_id', '555')->first();
        $this->assertTrue($conversation->lead_captured);
        // El sistema en modo FAQ nunca agenda nada — solo captura el contacto.
        $this->assertDatabaseCount('appointments', 0);
    }
}
