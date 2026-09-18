<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoiceRegistrationSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_la_configuracion(): void
    {
        $this->get('/admin/voice-registration-settings')->assertRedirect(route('login'));
    }

    public function test_un_administrador_normal_no_puede_ver_la_configuracion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/voice-registration-settings')->assertForbidden();
    }

    public function test_super_admin_puede_ver_la_configuracion_y_se_genera_un_secreto_de_webhook(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get('/admin/voice-registration-settings');

        $response->assertOk();
        $settings = app(SiteSettingsService::class)->get('voice_registration');
        $this->assertNotEmpty($settings['webhook_secret']);
    }

    public function test_super_admin_puede_guardar_la_configuracion(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->put('/admin/voice-registration-settings', [
            'enabled' => '1',
            'assistant_id' => 'assistant-voice-registration',
        ]);

        $settings = app(SiteSettingsService::class)->get('voice_registration');
        $this->assertTrue($settings['enabled']);
        $this->assertSame('assistant-voice-registration', $settings['assistant_id']);
    }

    public function test_regenerar_el_secreto_del_webhook_cambia_el_valor(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->get('/admin/voice-registration-settings');
        $original = app(SiteSettingsService::class)->get('voice_registration')['webhook_secret'];

        $this->actingAs($superAdmin)->post('/admin/voice-registration-settings/regenerate-webhook-secret');

        $regenerated = app(SiteSettingsService::class)->get('voice_registration')['webhook_secret'];
        $this->assertNotSame($original, $regenerated);
    }

    private function configureVoiceRegistration(): User
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('vapi', [
            'api_key' => 'vapi-key', 'assistant_id' => 'assistant-appointments', 'phone_number_id' => 'phone-1',
        ]);
        app(SiteSettingsService::class)->set('voice_registration', [
            'enabled' => true, 'assistant_id' => 'assistant-voice-registration', 'webhook_secret' => 'voice-secret',
        ]);

        return $superAdmin;
    }

    public function test_llamada_de_prueba_dispara_la_llamada(): void
    {
        $superAdmin = $this->configureVoiceRegistration();
        Http::fake(['api.vapi.ai/call' => Http::response(['id' => 'call-1'], 200)]);

        $response = $this->actingAs($superAdmin)->post('/admin/voice-registration-settings/send-test-call', [
            'test_phone' => '77778888',
        ]);

        $response->assertSessionHas('voice_registration_test_result', fn ($result) => $result['ok'] === true);
        Http::assertSent(fn ($request) => $request['assistantId'] === 'assistant-voice-registration'
            && $request['customer']['number'] === '+50377778888');
    }

    public function test_llamada_de_prueba_requiere_telefono(): void
    {
        $superAdmin = $this->configureVoiceRegistration();

        $this->actingAs($superAdmin)->post('/admin/voice-registration-settings/send-test-call', [])
            ->assertSessionHasErrors(['test_phone']);
    }

    public function test_llamada_de_prueba_reporta_error_si_vapi_rechaza_la_solicitud(): void
    {
        $superAdmin = $this->configureVoiceRegistration();
        Http::fake(['api.vapi.ai/call' => Http::response(['message' => 'error'], 500)]);

        $response = $this->actingAs($superAdmin)->post('/admin/voice-registration-settings/send-test-call', [
            'test_phone' => '77778888',
        ]);

        $response->assertSessionHas('voice_registration_test_result', fn ($result) => $result['ok'] === false);
    }

    public function test_es_delegable_a_un_admin_igual_que_las_demas_secciones_sensibles(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $adminRole = Role::where('slug', 'admin')->first();
        $adminUser = User::factory()->create(['role' => 'admin']);

        $this->actingAs($adminUser)->get('/admin/voice-registration-settings')->assertForbidden();

        $historicFeatures = array_keys(array_filter($adminRole->permissionsOrDefault()));
        $this->actingAs($superAdmin)->put("/admin/roles/{$adminRole->id}", [
            'name' => 'Administrador',
            'features' => array_merge($historicFeatures, ['voice-registration-settings']),
        ]);

        $this->actingAs($adminUser)->get('/admin/voice-registration-settings')->assertOk();
    }
}
