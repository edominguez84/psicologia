<?php

namespace Tests\Feature\Admin;

use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class ChatbotChannelsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_la_configuracion(): void
    {
        $this->get('/admin/chatbot-channels')->assertRedirect(route('login'));
    }

    public function test_un_administrador_normal_no_puede_ver_la_configuracion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/chatbot-channels')->assertForbidden();
    }

    public function test_super_admin_puede_guardar_credenciales_de_los_tres_canales(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->put('/admin/chatbot-channels', [
            'telegram_enabled' => '1',
            'telegram_bot_token' => '123456:ABC-test-token',
            'anthropic_api_key' => 'sk-ant-test',
            'anthropic_model' => 'claude-3-5-haiku-latest',
            'whatsapp_phone_number_id' => '999',
            'whatsapp_access_token' => 'wa-token',
            'whatsapp_verify_token' => 'verify123',
            'facebook_page_id' => '888',
            'facebook_page_access_token' => 'fb-token',
            'facebook_verify_token' => 'verify456',
        ]);

        $channels = app(SiteSettingsService::class)->get('chatbot_channels');
        $this->assertTrue($channels['telegram']['enabled']);
        $this->assertSame('123456:ABC-test-token', $channels['telegram']['bot_token']);
        $this->assertSame('sk-ant-test', $channels['anthropic']['api_key']);
        $this->assertSame('999', $channels['whatsapp']['phone_number_id']);
        $this->assertSame('888', $channels['facebook']['page_id']);
    }

    public function test_probar_conexion_de_telegram_exitosa_registra_el_webhook(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'mi_bot']], 200),
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true], 200),
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => '123:abc'],
        ]);

        $response = $this->actingAs($superAdmin)->post('/admin/chatbot-channels/test-telegram');

        $response->assertRedirect();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'setWebhook'));
    }

    public function test_probar_conexion_de_telegram_fallida_muestra_el_error(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'telegram' => ['enabled' => true, 'bot_token' => 'invalido'],
        ]);

        $response = $this->actingAs($superAdmin)->post('/admin/chatbot-channels/test-telegram');

        $response->assertSessionHas('telegram_test_result', fn ($result) => $result['ok'] === false);
    }

    public function test_guarda_el_interruptor_de_ia_y_la_temperatura(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->put('/admin/chatbot-channels', [
            'anthropic_enabled' => '1',
            'anthropic_api_key' => 'sk-ant-test',
            'anthropic_model' => 'claude-3-5-haiku-latest',
            'anthropic_temperature' => '0.8',
        ]);

        $channels = app(SiteSettingsService::class)->get('chatbot_channels');
        $this->assertTrue($channels['anthropic']['enabled']);
        // assertSame (no assertEquals): el bug real era que se guardaba el
        // string "0.8" tal cual llegaba del <input type="range">, y
        // Anthropic rechaza temperature si no es un número JSON real.
        $this->assertSame(0.8, $channels['anthropic']['temperature']);
    }

    public function test_guardar_configuracion_no_borra_el_pdf_fuente_ya_cargado(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['pdf_source_path' => 'chatbot-sources/existente.pdf', 'pdf_source_name' => 'existente.pdf', 'pdf_source_text' => 'Texto ya extraído.'],
        ]);

        $this->actingAs($superAdmin)->put('/admin/chatbot-channels', ['anthropic_api_key' => 'sk-ant-test']);

        $channels = app(SiteSettingsService::class)->get('chatbot_channels');
        $this->assertSame('existente.pdf', $channels['anthropic']['pdf_source_name']);
        $this->assertSame('Texto ya extraído.', $channels['anthropic']['pdf_source_text']);
    }

    public function test_super_admin_puede_subir_un_pdf_fuente_y_se_extrae_su_texto(): void
    {
        Storage::fake('public');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $pdf = new UploadedFile(base_path('tests/fixtures/sample.pdf'), 'tarifario.pdf', 'application/pdf', null, true);

        $response = $this->actingAs($superAdmin)->post('/admin/chatbot-channels/pdf-source', ['pdf_source' => $pdf]);

        $response->assertRedirect();
        $channels = app(SiteSettingsService::class)->get('chatbot_channels');
        $this->assertSame('tarifario.pdf', $channels['anthropic']['pdf_source_name']);
        $this->assertStringContainsString('Tarifario especial', $channels['anthropic']['pdf_source_text']);
        Storage::disk('public')->assertExists($channels['anthropic']['pdf_source_path']);
    }

    public function test_super_admin_puede_quitar_el_pdf_fuente(): void
    {
        Storage::fake('public');
        $path = Storage::disk('public')->put('chatbot-sources', new UploadedFile(base_path('tests/fixtures/sample.pdf'), 'x.pdf', 'application/pdf', null, true));
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['pdf_source_path' => $path, 'pdf_source_name' => 'x.pdf', 'pdf_source_text' => 'algo'],
        ]);

        $response = $this->actingAs($superAdmin)->delete('/admin/chatbot-channels/pdf-source');

        $response->assertRedirect();
        $channels = app(SiteSettingsService::class)->get('chatbot_channels');
        $this->assertNull($channels['anthropic']['pdf_source_name']);
        $this->assertSame('', $channels['anthropic']['pdf_source_text']);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_un_administrador_normal_no_puede_subir_pdf_fuente(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pdf = new UploadedFile(base_path('tests/fixtures/sample.pdf'), 'x.pdf', 'application/pdf', null, true);

        $this->actingAs($admin)->post('/admin/chatbot-channels/pdf-source', ['pdf_source' => $pdf])->assertForbidden();
    }
}
