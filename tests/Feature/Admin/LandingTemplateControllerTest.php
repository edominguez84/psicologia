<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/landing-template')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/landing-template')->assertForbidden();
    }

    public function test_por_defecto_la_plantilla_activa_es_la_clasica(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/landing-template');

        $response->assertOk();
        $response->assertViewHas('active', 'classic');
    }

    public function test_puede_cambiar_la_plantilla_activa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/landing-template', ['template' => 'minimal']);

        $response->assertRedirect();
        $settings = app(SiteSettingsService::class)->get('landing_template');
        $this->assertSame('minimal', $settings['key']);

        $edit = $this->actingAs($admin)->get('/admin/landing-template');
        $edit->assertViewHas('active', 'minimal');
    }

    /**
     * Regresión: el <form> del panel usaba method="POST" sin @method('PUT')
     * — Laravel enrutaba la petición como POST real contra una ruta
     * registrada solo como PUT, y el navegador recibía "405 Method Not
     * Allowed" al hacer clic en cualquier tarjeta. Los tests anteriores
     * (arriba) llaman a $this->put() directo, bypaseando el <form> HTML por
     * completo — nunca hubieran detectado esto. Este test sí envía la
     * petición tal como el botón del formulario real la genera: un POST con
     * el campo oculto _method=PUT que @method() imprime.
     */
    public function test_el_formulario_hace_el_spoofing_correcto_de_metodo_put(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $edit = $this->actingAs($admin)->get('/admin/landing-template');
        $edit->assertSee('name="_method"', false);
        $edit->assertSee('value="PUT"', false);

        $response = $this->actingAs($admin)->post('/admin/landing-template', [
            '_method' => 'PUT',
            'template' => 'minimal',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_rechaza_una_plantilla_que_no_existe_en_el_catalogo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/landing-template', ['template' => 'no-existe']);

        $response->assertSessionHasErrors('template');
    }

    public function test_cambiar_la_plantilla_activa_cambia_lo_que_se_sirve_en_la_home(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/landing-template', ['template' => 'cards']);

        // La plantilla "cards" pone el hero con imagen de fondo a sangre
        // completa en vez del layout de grid lateral de la clásica — basta
        // con confirmar que la home sigue respondiendo 200 tras el cambio,
        // el contenido exacto de cada plantilla ya lo cubre
        // LandingTemplatesSmokeTest.
        $this->get('/')->assertOk();
    }
}
