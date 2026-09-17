<?php

namespace Tests\Unit;

use App\Models\AppointmentSlot;
use App\Models\ChatbotFaq;
use App\Models\ChatbotLead;
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
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'model' => 'claude-3-5-haiku-latest'],
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
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Las sesiones duran 50 minutos.']],
                'stop_reason' => 'end_turn',
            ], 200),
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

    public function test_no_es_usable_si_esta_apagada_aunque_haya_api_key(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => false, 'api_key' => 'sk-ant-test'],
        ]);

        $this->assertFalse(app(ChatbotAiService::class)->isUsable());
    }

    public function test_es_usable_si_esta_activada_y_con_api_key(): void
    {
        $this->configureCredentials();

        $this->assertTrue(app(ChatbotAiService::class)->isUsable());
    }

    public function test_usa_la_tool_de_horarios_cuando_el_modelo_la_pide(): void
    {
        $this->configureCredentials();
        AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'get_available_slots', 'input' => []]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Tengo un horario disponible para ti.']],
                ], 200),
        ]);

        $reply = app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => '¿Qué horarios tienes?']]);

        $this->assertSame('Tengo un horario disponible para ti.', $reply);
        Http::assertSentCount(2);
    }

    public function test_usa_la_tool_de_guardar_lead_y_persiste_el_cliente_potencial(): void
    {
        $this->configureCredentials();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'save_lead', 'input' => [
                        'name' => 'María Pérez', 'email' => 'maria@example.com', 'phone' => '77778888',
                    ]]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Gracias, María, ya tengo tus datos.']],
                ], 200),
        ]);

        $captured = false;
        $reply = app(ChatbotAiService::class)->reply(
            [['role' => 'user', 'content' => 'Quiero agendar, mi nombre es María Pérez, mi correo es maria@example.com y mi teléfono 77778888']],
            ['channel' => 'telegram', 'external_chat_id' => '999'],
            function () use (&$captured) { $captured = true; }
        );

        $this->assertSame('Gracias, María, ya tengo tus datos.', $reply);
        $this->assertTrue($captured);
        $this->assertDatabaseHas('chatbot_leads', ['name' => 'María Pérez', 'email' => 'maria@example.com', 'phone' => '77778888']);
    }

    public function test_no_guarda_un_lead_con_correo_invalido(): void
    {
        $this->configureCredentials();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'save_lead', 'input' => [
                        'name' => 'María', 'email' => 'no-es-un-correo',
                    ]]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => '¿Me confirmas tu correo?']],
                ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Mi correo es no-es-un-correo']]);

        $this->assertSame(0, ChatbotLead::count());
    }

    public function test_envia_la_temperatura_configurada_a_la_api(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'temperature' => 0.7],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => ($request['temperature'] ?? null) === 0.7);
    }

    public function test_incluye_el_texto_del_pdf_fuente_en_el_system_prompt(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'pdf_source_text' => 'Tarifario especial: consulta $40.'],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => str_contains($request['system'], 'Tarifario especial: consulta $40.'));
    }
}
