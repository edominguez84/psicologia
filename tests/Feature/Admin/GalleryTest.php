<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/admin/gallery')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/gallery')->assertForbidden();
    }

    public function test_administradora_ve_las_imagenes_de_fabrica_por_defecto(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/gallery');

        $response->assertOk();
        $response->assertViewHas('images', function ($images) {
            return count($images) === count(config('site.gallery.images'));
        });
    }

    public function test_administradora_puede_anadir_una_imagen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->image('nueva.jpg', 1200, 800);

        $response = $this->actingAs($admin)->post('/admin/gallery', [
            'image' => $file,
            'alt'   => 'Una imagen de prueba',
        ]);

        $response->assertRedirect();
        $saved = SiteSetting::where('key', 'gallery')->first()->value;
        $this->assertCount(count(config('site.gallery.images')) + 1, $saved['images']);
        Storage::disk('public')->assertExists($saved['images'][count($saved['images']) - 1]['path']);
    }

    public function test_administradora_puede_reordenar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $defaults = config('site.gallery.images');

        $reordered = array_reverse($defaults);
        $response = $this->actingAs($admin)->put('/admin/gallery', ['images' => $reordered]);

        $response->assertRedirect();
        $saved = SiteSetting::where('key', 'gallery')->first()->value;
        $this->assertSame($reordered[0]['path'], $saved['images'][0]['path']);
    }

    public function test_eliminar_una_imagen_subida_borra_el_archivo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->image('nueva.jpg', 1200, 800);
        $this->actingAs($admin)->post('/admin/gallery', ['image' => $file, 'alt' => 'Prueba']);

        $saved = SiteSetting::where('key', 'gallery')->first()->value;
        $lastIndex = count($saved['images']) - 1;
        $path = $saved['images'][$lastIndex]['path'];

        $response = $this->actingAs($admin)->delete("/admin/gallery/{$lastIndex}");

        $response->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $updated = SiteSetting::where('key', 'gallery')->first()->value;
        $this->assertCount(count(config('site.gallery.images')), $updated['images']);
    }

    public function test_eliminar_una_imagen_de_fabrica_no_intenta_borrar_archivo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // La primera vez que se elimina algo, se parte de los defaults de config.
        $response = $this->actingAs($admin)->delete('/admin/gallery/0');

        $response->assertRedirect();
        $saved = SiteSetting::where('key', 'gallery')->first()->value;
        $this->assertCount(count(config('site.gallery.images')) - 1, $saved['images']);
    }
}
