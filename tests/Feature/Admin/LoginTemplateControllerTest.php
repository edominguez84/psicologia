<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/login-template')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/login-template')->assertForbidden();
    }

    public function test_por_defecto_la_plantilla_activa_es_la_clasica(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/login-template');

        $response->assertOk();
        $response->assertViewHas('active', 'classic');
    }

    public function test_puede_cambiar_a_la_plantilla_carrusel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $edit = $this->actingAs($admin)->put('/admin/login-template', ['template' => 'carousel']);

        $edit->assertRedirect();
        $settings = app(SiteSettingsService::class)->get('login_template');
        $this->assertSame('carousel', $settings['key']);
    }

    public function test_rechaza_una_plantilla_que_no_existe(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/login-template', ['template' => 'no-existe']);

        $response->assertSessionHasErrors('template');
    }

    public function test_el_formulario_incluye_el_spoofing_correcto_de_metodo_put(): void
    {
        // Regresión del mismo bug que ya se dio en /admin/landing-template:
        // un <form method="POST"> sin @method('PUT') contra una ruta
        // registrada solo como PUT da 405 Method Not Allowed.
        $admin = User::factory()->create(['role' => 'admin']);

        $edit = $this->actingAs($admin)->get('/admin/login-template');
        $edit->assertSee('name="_method"', false);
        $edit->assertSee('value="PUT"', false);
    }
}
