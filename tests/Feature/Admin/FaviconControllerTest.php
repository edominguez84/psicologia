<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaviconControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/favicon')->assertRedirect(route('login'));
    }

    public function test_admin_normal_no_puede_gestionar_el_favicon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/favicon')->assertForbidden();
    }

    public function test_super_admin_puede_subir_un_favicon(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $file = UploadedFile::fake()->image('icon.png', 64, 64)->size(100);

        $response = $this->actingAs($superAdmin)->post('/admin/favicon', ['favicon' => $file]);

        $response->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'favicon']);
    }

    public function test_rechaza_un_favicon_demasiado_grande_en_dimensiones(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $file = UploadedFile::fake()->image('icon.png', 1000, 1000);

        $response = $this->actingAs($superAdmin)->post('/admin/favicon', ['favicon' => $file]);

        $response->assertSessionHasErrors('favicon');
    }

    public function test_super_admin_puede_quitar_el_favicon_y_volver_al_default(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $file = UploadedFile::fake()->image('icon.png', 64, 64)->size(100);
        $this->actingAs($superAdmin)->post('/admin/favicon', ['favicon' => $file]);

        $response = $this->actingAs($superAdmin)->delete('/admin/favicon');

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_settings', ['key' => 'favicon']);
    }
}
