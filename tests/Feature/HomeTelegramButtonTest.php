<?php

namespace Tests\Feature;

use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El botón "Chatea con nosotros en Telegram" de la landing solo debe verse
 * si el canal está activo Y ya se obtuvo un username real probando la
 * conexión (ver Admin\ChatbotChannelsController::testTelegram()) — nunca
 * antes, para no enlazar a un bot que todavía no existe/no está probado.
 */
class HomeTelegramButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_telegram_configurado_no_se_muestra_el_boton(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('t.me/', false);
    }

    public function test_telegram_activo_sin_username_probado_no_muestra_el_boton(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => '123:abc', 'username' => ''],
        ]);

        $response = $this->get('/');

        $response->assertDontSee('t.me/', false);
    }

    public function test_telegram_activo_con_username_muestra_el_boton(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => '123:abc', 'username' => 'PsicologaSv_bot'],
        ]);

        $response = $this->get('/');

        $response->assertSee('https://t.me/PsicologaSv_bot', false);
        // Regresión: '@{{ $telegramUsername }}' (escapado a propósito por
        // Blade, para no interpolar) se imprimía tal cual en vez del
        // username real — el texto visible debe ser "@PsicologaSv_bot".
        $response->assertSee('@PsicologaSv_bot');
        $response->assertDontSee('{{ $telegramUsername }}');
    }

    public function test_telegram_desactivado_no_muestra_el_boton_aunque_tenga_username_guardado(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => false, 'bot_token' => '123:abc', 'username' => 'PsicologaSv_bot'],
        ]);

        $response = $this->get('/');

        $response->assertDontSee('t.me/', false);
    }
}
