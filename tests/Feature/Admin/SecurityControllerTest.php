<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SecurityAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_admin_no_puede_ver_los_ajustes(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/security')->assertForbidden();
    }

    public function test_admin_puede_guardar_los_toggles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/security', [
            'channels' => ['sms' => '1'],
            'oauth' => ['google' => '1'],
            'trusted_device_days' => 45,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'security']);
    }

    public function test_activar_un_canal_sin_credenciales_no_lo_vuelve_disponible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/security', [
            'channels' => ['sms' => '1'],
            'trusted_device_days' => 30,
        ]);

        config(['services.twilio.sid' => null, 'services.twilio.token' => null]);

        $availability = app(SecurityAvailability::class);
        $this->assertFalse($availability->channels()['sms']);
        $this->assertTrue($availability->armedChannels()['sms']);
    }

    public function test_activar_un_canal_con_credenciales_si_queda_disponible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/security', [
            'channels' => ['sms' => '1'],
            'trusted_device_days' => 30,
        ]);

        config(['services.twilio.sid' => 'test-sid', 'services.twilio.token' => 'test-token']);

        $this->assertTrue(app(SecurityAvailability::class)->channels()['sms']);
    }
}
