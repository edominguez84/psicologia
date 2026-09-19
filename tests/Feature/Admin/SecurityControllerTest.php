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
            'inactivity_timeout_minutes' => 30,
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
            'inactivity_timeout_minutes' => 30,
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
            'inactivity_timeout_minutes' => 30,
        ]);

        config(['services.twilio.sid' => 'test-sid', 'services.twilio.token' => 'test-token']);

        $this->assertTrue(app(SecurityAvailability::class)->channels()['sms']);
    }

    public function test_el_formulario_incluye_el_spoofing_correcto_de_metodo_put(): void
    {
        // Regresión: el <form> tenía method="PUT" en el HTML, un valor
        // inválido que todo navegador real trata como GET silenciosamente
        // (el atributo method solo acepta GET/POST) — el _method=PUT viajaba
        // como query string y Laravel lo ignoraba por venir en un GET, así
        // que el guardado nunca llegaba a update() y la página solo volvía a
        // mostrar los valores tal cual estaban, dando la impresión de que el
        // checkbox marcado "se reseteaba" al guardar. El form debe declarar
        // method="POST" en el HTML; @method('PUT') es lo que lo traduce a
        // PUT del lado del servidor.
        $admin = User::factory()->create(['role' => 'admin']);

        $edit = $this->actingAs($admin)->get('/admin/security');
        $edit->assertSee('method="POST"', false);
        $edit->assertDontSee('method="PUT"', false);
        $edit->assertSee('name="_method"', false);
        $edit->assertSee('value="PUT"', false);
    }
}
