<?php

namespace Tests\Unit;

use App\Services\SiteSettingsService;
use App\Services\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TelegramBotServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configureCredentials(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'test-token'],
        ]);
    }

    public function test_no_esta_habilitado_sin_credenciales(): void
    {
        $this->assertFalse(app(TelegramBotService::class)->isEnabled());
    }

    public function test_esta_habilitado_con_credenciales_y_flag_activo(): void
    {
        $this->configureCredentials();

        $this->assertTrue(app(TelegramBotService::class)->isEnabled());
    }

    public function test_get_me_devuelve_la_identidad_del_bot(): void
    {
        $this->configureCredentials();
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'mi_bot']], 200),
        ]);

        $me = app(TelegramBotService::class)->getMe();

        $this->assertSame('mi_bot', $me['username']);
    }

    public function test_lanza_excepcion_si_telegram_rechaza_el_token(): void
    {
        $this->configureCredentials();
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);

        $this->expectException(RuntimeException::class);

        app(TelegramBotService::class)->getMe();
    }

    public function test_verifica_el_secret_token_correctamente(): void
    {
        $this->configureCredentials();
        $service = app(TelegramBotService::class);

        $this->assertTrue($service->verifySecretToken('test-token'));
        $this->assertFalse($service->verifySecretToken('otro-token'));
        $this->assertFalse($service->verifySecretToken(null));
    }

    public function test_send_message_llama_al_endpoint_correcto(): void
    {
        $this->configureCredentials();
        Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);

        app(TelegramBotService::class)->sendMessage(12345, 'Hola');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] === 12345
                && $request['text'] === 'Hola';
        });
    }
}
