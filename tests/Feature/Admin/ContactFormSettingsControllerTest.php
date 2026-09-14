<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/contact-form')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/contact-form')->assertForbidden();
    }

    public function test_por_defecto_todos_los_campos_fijos_aparecen_visibles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/contact-form');

        $response->assertOk();
        $response->assertViewHas('visibleFields', fn ($fields) => $fields['phone'] === true && $fields['subject'] === true);
    }

    public function test_administradora_puede_ocultar_un_campo_fijo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/contact-form', ['fields' => ['subject']]);

        $settings = app(SiteSettingsService::class)->get('contact_form');
        $this->assertFalse($settings['fields']['phone']);
        $this->assertTrue($settings['fields']['subject']);
    }

    public function test_ocultar_un_campo_se_refleja_en_las_props_del_formulario_publico(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put('/admin/contact-form', ['fields' => ['subject', 'preferred_contact']]);

        $home = $this->get('/');

        $home->assertOk();
        // phone quedó desmarcado (no viaja en 'fields[]'), así que
        // visibleFields.phone debe llegar como false al componente Vue.
        $home->assertSee('&quot;phone&quot;:false', false);
        $home->assertSee('&quot;subject&quot;:true', false);
    }

    public function test_administradora_puede_anadir_un_campo_personalizado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/contact-form/custom-fields', [
            'label' => '¿Cómo nos conociste?',
            'required' => '1',
        ]);

        $response->assertRedirect();
        $settings = app(SiteSettingsService::class)->get('contact_form');
        $this->assertCount(1, $settings['custom_fields']);
        $this->assertSame('¿Cómo nos conociste?', $settings['custom_fields'][0]['label']);
        $this->assertTrue($settings['custom_fields'][0]['required']);
    }

    public function test_administradora_puede_eliminar_un_campo_personalizado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/contact-form/custom-fields', ['label' => 'Campo de prueba']);
        $settings = app(SiteSettingsService::class)->get('contact_form');
        $key = $settings['custom_fields'][0]['key'];

        $response = $this->actingAs($admin)->delete("/admin/contact-form/custom-fields/{$key}");

        $response->assertRedirect();
        $settings = app(SiteSettingsService::class)->get('contact_form');
        $this->assertCount(0, $settings['custom_fields']);
    }
}
