<?php

namespace Tests\Unit;

use App\Models\ChatbotFaq;
use App\Services\ChatbotAiService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ChatbotAiServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configureCredentials(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['api_key' => 'sk-ant-test', 'model' => 'claude-3-5-haiku-latest'],
        ]);
    }

    public function test_no_esta_configurado_sin_api_key(): void
    {
        $this->assertFalse(app(ChatbotAiService::class)->isConfigured());
    }

    public function test_esta_configurado_con_api_key(): void
    {
        $this->configureCredentials();

        $this->assertTrue(app(ChatbotAiService::class)->isConfigured());
    }

    public function test_reply_incluye_las_faqs_activas_en_el_system_prompt(): void
    {
        $this->configureCredentials();
        ChatbotFaq::create(['question' => '¿Cuánto dura una sesión?', 'answer' => '50 minutos.', 'is_active' => true]);
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['text' => 'Las sesiones duran 50 minutos.']]], 200),
        ]);

        $reply = app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => '¿Cuánto dura la sesión?']]);

        $this->assertSame('Las sesiones duran 50 minutos.', $reply);
        Http::assertSent(fn ($request) => str_contains($request['system'], '¿Cuánto dura una sesión?'));
    }

    public function test_lanza_excepcion_sin_api_key(): void
    {
        $this->expectException(RuntimeException::class);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);
    }

    public function test_lanza_excepcion_si_anthropic_rechaza_la_solicitud(): void
    {
        $this->configureCredentials();
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'invalid_api_key']], 401)]);

        $this->expectException(RuntimeException::class);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);
    }
}
