<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/about-photo')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/about-photo')->assertForbidden();
    }

    public function test_administradora_puede_subir_una_foto(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->image('foto.jpg', 800, 1000);

        $response = $this->actingAs($admin)->post('/admin/about-photo', ['photo' => $file]);

        $response->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'about_photo']);

        $saved = SiteSetting::where('key', 'about_photo')->first()->value;
        Storage::disk('public')->assertExists($saved['path']);
    }

    public function test_restaurar_borra_el_archivo_y_la_fila(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->image('foto.jpg', 800, 1000);
        $this->actingAs($admin)->post('/admin/about-photo', ['photo' => $file]);

        $path = SiteSetting::where('key', 'about_photo')->first()->value['path'];

        $response = $this->actingAs($admin)->delete('/admin/about-photo');

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_settings', ['key' => 'about_photo']);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->create('documento.pdf', 100);

        $response = $this->actingAs($admin)->post('/admin/about-photo', ['photo' => $file]);

        $response->assertSessionHasErrors('photo');
    }
}
