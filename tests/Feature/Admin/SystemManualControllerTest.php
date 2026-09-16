<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemManualControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_manual(): void
    {
        $this->get('/admin/system-manual')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/system-manual')->assertForbidden();
    }

    public function test_un_administrador_normal_puede_ver_el_manual(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/system-manual')->assertOk();
    }

    public function test_super_admin_puede_ver_el_manual(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->get('/admin/system-manual')->assertOk();
    }

    public function test_un_administrador_puede_descargar_el_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/system-manual/download');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
