<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionVisibilityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/section-visibility')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/section-visibility')->assertForbidden();
    }

    public function test_por_defecto_todas_las_secciones_ocultables_aparecen_como_visibles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/section-visibility');

        $response->assertOk();
        $response->assertViewHas('visibility', function ($visibility) {
            return $visibility['myths'] === true && $visibility['benefits'] === true;
        });
    }

    public function test_ocultar_una_seccion_hace_que_desaparezca_de_la_home(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // No se envía 'myths' en el array 'visible' => queda oculta.
        $this->actingAs($admin)->put('/admin/section-visibility', [
            'visible' => ['about', 'services', 'benefits', 'emdr', 'testimonials', 'checkup', 'faq'],
        ]);

        $home = $this->get('/');

        $home->assertOk();
        $home->assertDontSee('id="mitos"', false);
        $home->assertSee('id="beneficios"', false);
    }

    public function test_reactivar_una_seccion_oculta_la_vuelve_a_mostrar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/section-visibility', [
            'visible' => ['about', 'services', 'benefits', 'emdr', 'testimonials', 'checkup', 'faq'],
        ]);
        $this->get('/')->assertDontSee('id="mitos"', false);

        $this->actingAs($admin)->put('/admin/section-visibility', [
            'visible' => ['about', 'services', 'benefits', 'emdr', 'testimonials', 'myths', 'checkup', 'faq'],
        ]);

        $this->get('/')->assertSee('id="mitos"', false);
    }

    public function test_el_nav_no_enlaza_a_una_seccion_oculta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/section-visibility', [
            'visible' => ['about', 'services', 'emdr', 'testimonials', 'myths', 'checkup', 'faq'],
        ]);

        $home = $this->get('/');

        $home->assertOk();
        $home->assertDontSee('/#beneficios', false);
    }

    public function test_hero_no_se_puede_ocultar_porque_no_esta_en_el_formulario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/section-visibility');

        $response->assertOk();
        $response->assertViewHas('sections', function ($sections) {
            return ! array_key_exists('hero', $sections) && array_key_exists('contact_section', $sections);
        });

        // Aunque alguien manipulara el request para intentar enviar esa key,
        // la validación la rechaza por no estar en la lista permitida.
        $invalid = $this->actingAs($admin)->put('/admin/section-visibility', [
            'visible' => ['hero'],
        ]);
        $invalid->assertSessionHasErrors('visible.0');
    }

    public function test_el_formulario_de_contacto_se_puede_ocultar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/section-visibility', [
            'visible' => ['about', 'services', 'benefits', 'emdr', 'testimonials', 'myths', 'checkup', 'faq'],
        ]);

        $this->get('/')->assertDontSee('id="contacto"', false);
    }
}
