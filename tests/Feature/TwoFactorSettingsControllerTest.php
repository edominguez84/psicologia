<?php

namespace Tests\Feature;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_panel(): void
    {
        $this->get('/perfil/seguridad')->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_puede_ver_el_panel(): void
    {
        $user = User::factory()->create(['role' => 'patient']);

        $this->actingAs($user)->get('/perfil/seguridad')->assertOk();
    }

    public function test_puede_cambiar_el_metodo_a_sms_si_esta_disponible(): void
    {
        config([
            'services.twilio.sid' => 'test-sid',
            'services.twilio.token' => 'test-token',
        ]);
        $user = User::factory()->create(['role' => 'patient', 'two_factor_method' => 'email']);
        app(\App\Services\SiteSettingsService::class)->set('security', ['channels' => ['sms' => true]]);

        $response = $this->actingAs($user)->put(route('two-factor.method.update'), ['method' => 'sms']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('sms', $user->fresh()->two_factor_method);
    }

    public function test_no_puede_elegir_un_canal_no_disponible(): void
    {
        $user = User::factory()->create(['role' => 'patient', 'two_factor_method' => 'email']);

        $response = $this->actingAs($user)->put(route('two-factor.method.update'), ['method' => 'sms']);

        $response->assertSessionHasErrors('method');
        $this->assertSame('email', $user->fresh()->two_factor_method);
    }

    public function test_el_formulario_incluye_el_spoofing_correcto_de_metodo_put(): void
    {
        // Regresión: mismo bug que ya se dio en /admin/security y
        // /admin/landing-template — el <form> tenía method="PUT" en el
        // atributo HTML, valor inválido que todo navegador trata como GET
        // silenciosamente, así que el guardado nunca llegaba a
        // updateMethod() y el botón "Guardar método" no hacía nada visible.
        $user = User::factory()->create(['role' => 'patient']);

        $edit = $this->actingAs($user)->get('/perfil/seguridad');
        $edit->assertSee('method="POST"', false);
        $edit->assertDontSee('method="PUT"', false);
        $edit->assertSee('name="_method"', false);
        $edit->assertSee('value="PUT"', false);
    }

    public function test_puede_olvidar_un_dispositivo_de_confianza_propio(): void
    {
        $user = User::factory()->create(['role' => 'patient']);
        $device = TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => bcrypt('token'),
            'user_agent' => 'Test',
            'ip_address' => '127.0.0.1',
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->delete(route('two-factor.devices.forget', $device));

        $response->assertRedirect();
        $this->assertDatabaseMissing('trusted_devices', ['id' => $device->id]);
    }

    public function test_no_puede_olvidar_el_dispositivo_de_otro_usuario(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $device = TrustedDevice::create([
            'user_id' => $other->id,
            'token_hash' => bcrypt('token'),
            'user_agent' => 'Test',
            'ip_address' => '127.0.0.1',
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)->delete(route('two-factor.devices.forget', $device))->assertForbidden();
        $this->assertDatabaseHas('trusted_devices', ['id' => $device->id]);
    }
}
