<?php

namespace Tests\Feature\Admin;

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
}
