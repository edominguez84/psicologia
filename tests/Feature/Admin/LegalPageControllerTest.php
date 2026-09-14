<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/legal/privacy')->assertRedirect(route('login'));
    }

    public function test_administradora_no_super_recibe_403(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/legal/privacy')->assertForbidden();
    }

    public function test_super_admin_puede_ver_el_formulario(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->get('/admin/legal/privacy')->assertOk();
    }

    public function test_una_pagina_que_no_existe_devuelve_404(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->get('/admin/legal/inventada')->assertNotFound();
        $this->actingAs($superAdmin)->put('/admin/legal/inventada', ['title' => 'x', 'body_html' => '<p>x</p>'])->assertNotFound();
    }

    public function test_super_admin_puede_guardar_el_contenido(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->put('/admin/legal/terms', [
            'title' => 'Condiciones de uso',
            'body_html' => '<p>Texto <strong>importante</strong>.</p><ul><li>Uno</li></ul>',
        ]);

        $response->assertRedirect();
        $saved = app(SiteSettingsService::class)->get('legal_pages')['terms'];
        $this->assertSame('Condiciones de uso', $saved['title']);
        $this->assertStringContainsString('<strong>importante</strong>', $saved['body_html']);
        $this->assertStringContainsString('<li>Uno</li>', $saved['body_html']);
    }

    public function test_el_guardado_elimina_scripts_y_atributos_peligrosos(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->put('/admin/legal/privacy', [
            'title' => 'Política de privacidad',
            'body_html' => '<p onclick="alert(1)">Hola</p><script>alert(2)</script><img src=x onerror=alert(3)>',
        ]);

        $saved = app(SiteSettingsService::class)->get('legal_pages')['privacy'];
        $this->assertStringNotContainsString('<script', $saved['body_html']);
        $this->assertStringNotContainsString('onclick', $saved['body_html']);
        $this->assertStringNotContainsString('onerror', $saved['body_html']);
        $this->assertStringNotContainsString('<img', $saved['body_html']);
        $this->assertStringContainsString('Hola', $saved['body_html']);
    }

    public function test_el_contenido_guardado_se_refleja_en_la_pagina_publica(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->put('/admin/legal/privacy', [
            'title' => 'Mi política personalizada',
            'body_html' => '<p>Contenido personalizado de prueba.</p>',
        ]);

        $response = $this->get('/privacidad');

        $response->assertOk();
        $response->assertSee('Mi política personalizada');
        $response->assertSee('Contenido personalizado de prueba.');
    }
}
