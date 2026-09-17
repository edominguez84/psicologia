<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VapiSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_la_configuracion(): void
    {
        $this->get('/admin/vapi-settings')->assertRedirect(route('login'));
    }

    public function test_un_administrador_normal_no_puede_ver_la_configuracion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/vapi-settings')->assertForbidden();
    }

    public function test_super_admin_puede_ver_la_configuracion_y_se_genera_un_secreto_de_webhook(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get('/admin/vapi-settings');

        $response->assertOk();
        $settings = app(SiteSettingsService::class)->get('vapi');
        $this->assertNotEmpty($settings['webhook_secret']);
    }

    public function test_super_admin_puede_guardar_las_credenciales(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->put('/admin/vapi-settings', [
            'enabled' => '1',
            'api_key' => 'vapi-key',
            'assistant_id' => 'assistant-1',
            'phone_number_id' => 'phone-1',
            'hours_before' => '48',
        ]);

        $settings = app(SiteSettingsService::class)->get('vapi');
        $this->assertTrue($settings['enabled']);
        $this->assertSame('vapi-key', $settings['api_key']);
        $this->assertSame('assistant-1', $settings['assistant_id']);
        $this->assertSame('phone-1', $settings['phone_number_id']);
        $this->assertSame(48, $settings['hours_before']);
    }

    public function test_regenerar_el_secreto_del_webhook_cambia_el_valor(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->get('/admin/vapi-settings');
        $original = app(SiteSettingsService::class)->get('vapi')['webhook_secret'];

        $this->actingAs($superAdmin)->post('/admin/vapi-settings/regenerate-webhook-secret');

        $regenerated = app(SiteSettingsService::class)->get('vapi')['webhook_secret'];
        $this->assertNotSame($original, $regenerated);
    }

    public function test_probar_conexion_exitosa(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('vapi', ['api_key' => 'vapi-key']);
        Http::fake(['api.vapi.ai/assistant' => Http::response([], 200)]);

        $response = $this->actingAs($superAdmin)->post('/admin/vapi-settings/test-connection');

        $response->assertSessionHas('vapi_test_result', fn ($result) => $result['ok'] === true);
    }

    public function test_probar_conexion_fallida(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('vapi', ['api_key' => 'invalida']);
        Http::fake(['api.vapi.ai/assistant' => Http::response(['message' => 'Unauthorized'], 401)]);

        $response = $this->actingAs($superAdmin)->post('/admin/vapi-settings/test-connection');

        $response->assertSessionHas('vapi_test_result', fn ($result) => $result['ok'] === false);
    }

    public function test_es_delegable_a_un_admin_igual_que_las_demas_secciones_sensibles(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $adminRole = Role::where('slug', 'admin')->first();
        $adminUser = User::factory()->create(['role' => 'admin']);

        $this->actingAs($adminUser)->get('/admin/vapi-settings')->assertForbidden();

        $historicFeatures = array_keys(array_filter($adminRole->permissionsOrDefault()));
        $this->actingAs($superAdmin)->put("/admin/roles/{$adminRole->id}", [
            'name' => 'Administrador',
            'features' => array_merge($historicFeatures, ['vapi-settings']),
        ]);

        $this->actingAs($adminUser)->get('/admin/vapi-settings')->assertOk();
    }

    private function configureVapi(): User
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'phone_number' => '77778888']);
        app(SiteSettingsService::class)->set('vapi', [
            'api_key' => 'vapi-key', 'assistant_id' => 'assistant-1', 'phone_number_id' => 'phone-1',
        ]);

        return $superAdmin;
    }

    public function test_llamada_de_prueba_crea_cita_demo_y_dispara_la_llamada(): void
    {
        $superAdmin = $this->configureVapi();
        Http::fake(['api.vapi.ai/call' => Http::response(['id' => 'call-demo-1'], 200)]);

        $response = $this->actingAs($superAdmin)->post('/admin/vapi-settings/send-test-call');

        $response->assertSessionHas('vapi_test_result', fn ($result) => $result['ok'] === true);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.vapi.ai/call'
            && $request['assistantOverrides']['variableValues']['nombrePaciente'] === $superAdmin->name);

        $appointment = Appointment::where('user_id', $superAdmin->id)->first();
        $this->assertNotNull($appointment);
        $this->assertSame('approved', $appointment->status->value);
        $this->assertSame('scheduled', $appointment->vapi_call_status);
        $this->assertSame('call-demo-1', $appointment->vapi_call_id);
    }

    public function test_llamada_de_prueba_falla_ordenadamente_sin_telefono_en_el_perfil(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'phone_number' => null]);
        app(SiteSettingsService::class)->set('vapi', [
            'api_key' => 'vapi-key', 'assistant_id' => 'assistant-1', 'phone_number_id' => 'phone-1',
        ]);

        $response = $this->actingAs($superAdmin)->post('/admin/vapi-settings/send-test-call');

        $response->assertSessionHas('vapi_test_result', fn ($result) => $result['ok'] === false);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_llamada_de_prueba_no_deja_basura_si_vapi_rechaza_la_solicitud(): void
    {
        $superAdmin = $this->configureVapi();
        Http::fake(['api.vapi.ai/call' => Http::response(['message' => 'error'], 500)]);

        $response = $this->actingAs($superAdmin)->post('/admin/vapi-settings/send-test-call');

        $response->assertSessionHas('vapi_test_result', fn ($result) => $result['ok'] === false);
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('appointment_slots', 0);
    }

    public function test_las_citas_demo_anteriores_se_borran_al_volver_a_entrar_a_la_pantalla(): void
    {
        $superAdmin = $this->configureVapi();
        Http::fake(['api.vapi.ai/call' => Http::response(['id' => 'call-demo-1'], 200)]);
        $this->actingAs($superAdmin)->post('/admin/vapi-settings/send-test-call');
        $this->assertDatabaseCount('appointments', 1);

        $this->actingAs($superAdmin)->get('/admin/vapi-settings');

        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('appointment_slots', 0);
    }
}
