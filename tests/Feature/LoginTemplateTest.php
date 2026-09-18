<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ambas plantillas de login (classic/carousel) comparten exactamente el
 * mismo formulario/contrato — estos tests confirman que activar "carousel"
 * no rompe el login real, ni afecta otras vistas de auth que comparten
 * layouts/guest.blade.php con la plantilla clásica.
 */
class LoginTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_clasico_renderiza_el_formulario(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
    }

    public function test_login_carrusel_renderiza_el_mismo_formulario(): void
    {
        app(SiteSettingsService::class)->set('login_template', ['key' => 'carousel']);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('data-vue="Carousel"', false);
    }

    public function test_el_login_real_funciona_con_la_plantilla_carrusel_activa(): void
    {
        app(SiteSettingsService::class)->set('login_template', ['key' => 'carousel']);
        $user = User::factory()->create(['password' => bcrypt('password-segura-123')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-segura-123',
        ]);

        // No debe fallar por un problema de plantilla — puede redirigir a
        // 2FA o al panel según el flujo normal de autenticación, que no se
        // toca en absoluto.
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_una_key_de_plantilla_de_login_invalida_cae_a_la_clasica(): void
    {
        app(SiteSettingsService::class)->set('login_template', ['key' => 'plantilla-que-no-existe']);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('data-vue="Carousel"', false);
    }

    public function test_el_registro_no_se_ve_afectado_por_la_plantilla_de_login(): void
    {
        app(SiteSettingsService::class)->set('login_template', ['key' => 'carousel']);

        $response = $this->get('/register');

        $response->assertOk();
    }
}
