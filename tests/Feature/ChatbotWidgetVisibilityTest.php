<?php

namespace Tests\Feature;

use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El widget de chat flotante (data-vue="ChatbotWidget" en layouts/app.blade.php)
 * debe respetar site_settings.chatbot_channels.chat_widget.web_widget_enabled,
 * configurable en /admin/chatbot-channels — sin afectar el bot de Telegram,
 * que tiene su propio interruptor independiente.
 */
class ChatbotWidgetVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_widget_aparece_por_defecto(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-vue="ChatbotWidget"', false);
    }

    public function test_el_widget_se_oculta_si_esta_desactivado(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'chat_widget' => ['web_widget_enabled' => false, 'inactivity_timeout_minutes' => 5, 'farewell_message' => 'Adiós.'],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('data-vue="ChatbotWidget"', false);
    }

    public function test_el_widget_incluye_el_timeout_y_el_mensaje_de_despedida_configurados(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'chat_widget' => ['web_widget_enabled' => true, 'inactivity_timeout_minutes' => 15, 'farewell_message' => 'Nos vemos pronto.'],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('&quot;inactivityTimeoutMinutes&quot;:15', false);
        $response->assertSee('Nos vemos pronto.', false);
    }
}
