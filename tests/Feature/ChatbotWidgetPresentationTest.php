<?php

namespace Tests\Feature;

use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresión: el widget del sitio mostraba "Alexa" en el saludo inicial (de
 * site_settings.chatbot.name) mientras la IA respondía como "Cortana" (de
 * chatbot_channels.anthropic.bot_name) — dos configuraciones de nombre que
 * nunca se sincronizaban. Ver App\Services\ChatbotAiService::presentation()
 * y resources/views/layouts/app.blade.php.
 */
class ChatbotWidgetPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_con_ia_activa_el_widget_usa_el_nombre_configurado_para_la_ia(): void
    {
        app(SiteSettingsService::class)->set('chatbot', ['name' => 'Rebecca']);
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'bot_name' => 'Cortana'],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('&quot;botName&quot;:&quot;Cortana&quot;', false);
        $response->assertDontSee('&quot;botName&quot;:&quot;Rebecca&quot;', false);
    }

    public function test_sin_ia_activa_el_widget_usa_el_nombre_historico_de_preguntas_frecuentes(): void
    {
        app(SiteSettingsService::class)->set('chatbot', ['name' => 'Rebecca']);
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => false, 'api_key' => '', 'bot_name' => 'Cortana'],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('&quot;botName&quot;:&quot;Rebecca&quot;', false);
    }
}
