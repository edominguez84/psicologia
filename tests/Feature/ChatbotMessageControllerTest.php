<?php

namespace Tests\Feature;

use App\Models\ChatbotConversation;
use App\Models\ChatbotFaq;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_ia_activa_responde_en_modo_faq(): void
    {
        ChatbotFaq::query()->update(['is_active' => false]);
        ChatbotFaq::create(['question' => '¿Ofrecen descuentos por paquete de sesiones?', 'answer' => 'Sí, con paquete de 4 sesiones.', 'is_active' => true]);

        $response = $this->postJson('/chatbot-mensaje', ['message' => '¿Ofrecen descuentos por paquete de sesiones?']);

        $response->assertOk()->assertJson(['mode' => 'faq', 'reply' => 'Sí, con paquete de 4 sesiones.']);
    }

    public function test_con_ia_activa_responde_con_ia_y_guarda_la_conversacion(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test'],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Hola, ¿en qué puedo ayudarte?']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        $response = $this->postJson('/chatbot-mensaje', ['message' => 'Hola']);

        $response->assertOk()->assertJson(['mode' => 'ai', 'reply' => 'Hola, ¿en qué puedo ayudarte?']);
        $this->assertDatabaseCount('chatbot_conversations', 1);
        $this->assertSame('web', ChatbotConversation::first()->channel);
    }

    public function test_si_la_ia_falla_cae_a_modo_faq(): void
    {
        ChatbotFaq::query()->update(['is_active' => false]);
        ChatbotFaq::create(['question' => '¿Ofrecen descuentos por paquete de sesiones?', 'answer' => '$40 el paquete.', 'is_active' => true]);
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test'],
        ]);
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'rate_limited']], 429)]);

        $response = $this->postJson('/chatbot-mensaje', ['message' => '¿Ofrecen descuentos por paquete de sesiones?']);

        $response->assertOk()->assertJson(['mode' => 'faq', 'reply' => '$40 el paquete.']);
    }

    public function test_requiere_un_mensaje(): void
    {
        $this->postJson('/chatbot-mensaje', [])->assertUnprocessable();
    }
}
