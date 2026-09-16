<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_la_configuracion(): void
    {
        $this->get('/admin/role-permissions')->assertRedirect(route('login'));
    }

    public function test_un_administrador_normal_no_puede_ver_la_configuracion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/role-permissions')->assertForbidden();
    }

    public function test_super_admin_puede_ver_y_guardar_permisos(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->put('/admin/role-permissions', [
            'admin' => ['theme', 'messages'],
            'editor' => ['theme'],
        ]);

        $response->assertRedirect();
        $permissions = app(SiteSettingsService::class)->get('admin_role_permissions');
        $this->assertTrue($permissions['admin']['theme']);
        $this->assertTrue($permissions['admin']['messages']);
        $this->assertFalse($permissions['admin']['gallery']);
        $this->assertTrue($permissions['editor']['theme']);
        $this->assertFalse($permissions['editor']['messages']);
    }

    public function test_por_defecto_un_administrador_ve_todo_sin_configuracion_previa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/theme')->assertOk();
        $this->actingAs($admin)->get('/admin/messages')->assertOk();
    }

    public function test_desactivar_una_feature_bloquea_el_acceso_del_administrador(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->put('/admin/role-permissions', [
            'admin' => ['messages'], // theme queda fuera => desactivado
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/theme')->assertForbidden();
        $this->actingAs($admin)->get('/admin/messages')->assertOk();
    }

    public function test_los_permisos_no_afectan_a_super_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        app(SiteSettingsService::class)->set('admin_role_permissions', [
            'admin' => ['messages'],
        ]);

        $this->actingAs($superAdmin)->get('/admin/theme')->assertOk();
    }

    public function test_una_feature_desactivada_para_admin_no_afecta_a_editor(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->put('/admin/role-permissions', [
            'admin' => ['messages'],
            'editor' => ['theme', 'messages'],
        ]);

        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)->get('/admin/theme')->assertOk();
    }
}
