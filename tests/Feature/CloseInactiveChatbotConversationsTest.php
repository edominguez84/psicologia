<?php

namespace Tests\Feature;

use App\Models\ChatbotConversation;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloseInactiveChatbotConversationsTest extends TestCase
{
    use RefreshDatabase;

    private function configureTelegram(array $overrides = []): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', array_merge([
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token', 'webhook_secret' => 'secret'],
            'chat_widget' => [
                'web_widget_enabled' => true,
                'inactivity_timeout_minutes' => 5,
                'farewell_message' => 'Veo que no tienes otra consulta, buen día, adiós.',
            ],
        ], $overrides));
    }

    public function test_despide_y_cierra_una_conversacion_de_telegram_inactiva(): void
    {
        $this->configureTelegram();
        Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '555']);
        $conversation->timestamps = false;
        $conversation->forceFill(['updated_at' => now()->subMinutes(10)])->save();

        $this->artisan('chatbot:close-inactive-conversations')->assertSuccessful();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '555'
            && $request['text'] === 'Veo que no tienes otra consulta, buen día, adiós.');
        $this->assertNotNull($conversation->fresh()->closed_at);
    }

    public function test_no_despide_una_conversacion_todavia_activa(): void
    {
        $this->configureTelegram();
        Http::fake();
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '555']);

        $this->artisan('chatbot:close-inactive-conversations')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNull($conversation->fresh()->closed_at);
    }

    public function test_no_repite_la_despedida_en_una_conversacion_ya_cerrada(): void
    {
        $this->configureTelegram();
        Http::fake();
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '555']);
        $conversation->timestamps = false;
        $conversation->forceFill(['updated_at' => now()->subMinutes(10), 'closed_at' => now()->subMinutes(3)])->save();

        $this->artisan('chatbot:close-inactive-conversations')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_no_afecta_conversaciones_del_widget_web(): void
    {
        $this->configureTelegram();
        Http::fake();
        $conversation = ChatbotConversation::create(['channel' => 'web', 'external_chat_id' => 'session-abc']);
        $conversation->timestamps = false;
        $conversation->forceFill(['updated_at' => now()->subMinutes(10)])->save();

        $this->artisan('chatbot:close-inactive-conversations')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNull($conversation->fresh()->closed_at);
    }

    public function test_no_hace_nada_si_telegram_esta_apagado(): void
    {
        $this->configureTelegram(['telegram' => ['enabled' => false, 'bot_token' => 'test-token', 'webhook_secret' => 'secret']]);
        Http::fake();
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '555']);
        $conversation->timestamps = false;
        $conversation->forceFill(['updated_at' => now()->subMinutes(10)])->save();

        $this->artisan('chatbot:close-inactive-conversations')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNull($conversation->fresh()->closed_at);
    }

    public function test_respeta_el_timeout_configurado(): void
    {
        $this->configureTelegram(['chat_widget' => [
            'web_widget_enabled' => true, 'inactivity_timeout_minutes' => 20, 'farewell_message' => 'Adiós.',
        ]]);
        Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);
        $conversation = ChatbotConversation::create(['channel' => 'telegram', 'external_chat_id' => '555']);
        $conversation->timestamps = false;
        $conversation->forceFill(['updated_at' => now()->subMinutes(10)])->save();

        $this->artisan('chatbot:close-inactive-conversations')->assertSuccessful();

        // 10 minutos de inactividad, pero el timeout configurado es 20 —
        // todavía no debe despedirse.
        Http::assertNothingSent();
    }
}
