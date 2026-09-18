<?php

namespace Tests\Feature;

use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoiceRegistrationLeadControllerTest extends TestCase
{
    use RefreshDatabase;

    private function configureVoiceRegistration(): void
    {
        app(SiteSettingsService::class)->set('vapi', [
            'api_key' => 'vapi-key', 'assistant_id' => 'assistant-appointments', 'phone_number_id' => 'phone-1',
        ]);
        app(SiteSettingsService::class)->set('voice_registration', [
            'enabled' => true, 'assistant_id' => 'assistant-voice-registration', 'webhook_secret' => 'voice-secret',
        ]);
    }

    public function test_dispara_la_llamada_cuando_esta_activo_y_configurado(): void
    {
        $this->configureVoiceRegistration();
        Http::fake(['api.vapi.ai/call' => Http::response(['id' => 'call-1'], 200)]);

        $response = $this->postJson('/registro-por-llamada', ['phone_number' => '77778888']);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        Http::assertSent(fn ($request) => $request['assistantId'] === 'assistant-voice-registration'
            && $request['customer']['number'] === '+50377778888');
    }

    public function test_rechaza_con_mensaje_claro_cuando_esta_apagado(): void
    {
        $this->configureVoiceRegistration();
        app(SiteSettingsService::class)->set('voice_registration', [
            'enabled' => false, 'assistant_id' => 'assistant-voice-registration', 'webhook_secret' => 'voice-secret',
        ]);
        Http::fake();

        $response = $this->postJson('/registro-por-llamada', ['phone_number' => '77778888']);

        $response->assertStatus(503);
        $response->assertJson(['ok' => false]);
        Http::assertNothingSent();
    }

    public function test_requiere_telefono(): void
    {
        $this->configureVoiceRegistration();

        $response = $this->postJson('/registro-por-llamada', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['phone_number']);
    }

    public function test_reporta_error_controlado_si_vapi_rechaza_la_llamada(): void
    {
        $this->configureVoiceRegistration();
        Http::fake(['api.vapi.ai/call' => Http::response(['message' => 'error'], 500)]);

        $response = $this->postJson('/registro-por-llamada', ['phone_number' => '77778888']);

        $response->assertStatus(502);
        $response->assertJson(['ok' => false]);
    }
}
