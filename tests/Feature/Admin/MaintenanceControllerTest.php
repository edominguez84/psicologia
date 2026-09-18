<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/maintenance')->assertRedirect(route('login'));
    }

    public function test_un_administrador_normal_no_puede_ver_el_formulario_sin_el_permiso(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/maintenance')->assertForbidden();
    }

    public function test_super_admin_puede_activar_el_modo_mantenimiento(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->put('/admin/maintenance', ['enabled' => '1']);

        $response->assertRedirect();
        $this->assertTrue(app(SiteSettingsService::class)->get('maintenance')['enabled']);
    }

    public function test_super_admin_puede_desactivar_el_modo_mantenimiento(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('maintenance', ['enabled' => true]);

        // Checkbox desmarcado: no se envía 'enabled' en el request.
        $this->actingAs($superAdmin)->put('/admin/maintenance', []);

        $this->assertFalse(app(SiteSettingsService::class)->get('maintenance')['enabled']);
    }
}
