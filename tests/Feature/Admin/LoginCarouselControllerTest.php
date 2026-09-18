<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoginCarouselControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_subir_imagenes(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('foto.jpg');

        $this->post('/admin/login-carousel', ['image' => $image])->assertRedirect(route('login'));
    }

    public function test_admin_puede_subir_una_imagen_al_carrusel(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $image = UploadedFile::fake()->image('foto.jpg');

        $response = $this->actingAs($admin)->post('/admin/login-carousel', [
            'image' => $image,
            'alt' => 'Sesión de terapia',
        ]);

        $response->assertRedirect();
        $settings = app(SiteSettingsService::class)->get('login_carousel');
        // Se agrega a las 3 imágenes de fábrica ya presentes por defecto
        // (App\Http\Controllers\Admin\LoginCarouselController::DEFAULT_IMAGES) —
        // quedan 4 en total, la nueva al final.
        $this->assertCount(4, $settings['images']);
        $uploaded = end($settings['images']);
        $this->assertSame('Sesión de terapia', $uploaded['alt']);
        Storage::disk('public')->assertExists($uploaded['path']);
    }

    public function test_puede_reordenar_y_editar_el_texto_alternativo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        app(SiteSettingsService::class)->set('login_carousel', [
            'images' => [
                ['path' => 'images/gallery/therapy-1.jpg', 'alt' => 'Primera'],
                ['path' => 'images/gallery/therapy-2.jpg', 'alt' => 'Segunda'],
            ],
        ]);

        $this->actingAs($admin)->put('/admin/login-carousel', [
            'images' => [
                ['path' => 'images/gallery/therapy-2.jpg', 'alt' => 'Segunda editada'],
                ['path' => 'images/gallery/therapy-1.jpg', 'alt' => 'Primera'],
            ],
        ]);

        $settings = app(SiteSettingsService::class)->get('login_carousel');
        $this->assertSame('images/gallery/therapy-2.jpg', $settings['images'][0]['path']);
        $this->assertSame('Segunda editada', $settings['images'][0]['alt']);
    }

    public function test_puede_eliminar_una_imagen_subida_y_borra_el_archivo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $path = UploadedFile::fake()->image('foto.jpg')->store('login-carousel', 'public');
        app(SiteSettingsService::class)->set('login_carousel', [
            'images' => [['path' => $path, 'alt' => 'x']],
        ]);

        $response = $this->actingAs($admin)->delete('/admin/login-carousel/0');

        $response->assertRedirect();
        $settings = app(SiteSettingsService::class)->get('login_carousel');
        $this->assertCount(0, $settings['images']);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_eliminar_una_imagen_de_fabrica_no_borra_ningun_archivo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(SiteSettingsService::class)->set('login_carousel', [
            'images' => [['path' => 'images/gallery/therapy-1.jpg', 'alt' => 'x']],
        ]);

        $response = $this->actingAs($admin)->delete('/admin/login-carousel/0');

        $response->assertRedirect();
        $this->assertFileExists(public_path('images/gallery/therapy-1.jpg'));
    }
}
