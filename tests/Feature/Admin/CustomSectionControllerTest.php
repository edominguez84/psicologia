<?php

namespace Tests\Feature\Admin;

use App\Models\CustomSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_invitado_no_puede_ver_el_listado(): void
    {
        $this->get('/admin/custom-sections')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/custom-sections')->assertForbidden();
    }

    public function test_administradora_puede_crear_una_seccion_sin_imagen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/custom-sections', [
            'title' => 'Talleres grupales',
            'body' => 'Todos los meses organizamos un taller grupal.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('custom_sections', [
            'title' => 'Talleres grupales',
            'is_active' => 1,
            'image_path' => null,
        ]);
    }

    public function test_administradora_puede_crear_una_seccion_con_imagen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->image('seccion.jpg', 1200, 800);

        $response = $this->actingAs($admin)->post('/admin/custom-sections', [
            'title' => 'Con imagen',
            'body' => 'Texto de la sección.',
            'image' => $file,
        ]);

        $response->assertRedirect();
        $section = CustomSection::first();
        Storage::disk('public')->assertExists($section->image_path);
    }

    public function test_rechaza_una_imagen_demasiado_grande(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->image('grande.jpg', 3000, 3000);

        $response = $this->actingAs($admin)->post('/admin/custom-sections', [
            'title' => 'Prueba',
            'body' => 'Texto.',
            'image' => $file,
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('custom_sections', 0);
    }

    public function test_administradora_puede_editar_una_seccion_y_reemplazar_la_imagen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $original = UploadedFile::fake()->image('original.jpg', 800, 600);
        $this->actingAs($admin)->post('/admin/custom-sections', [
            'title' => 'Original',
            'body' => 'Texto original.',
            'image' => $original,
        ]);
        $section = CustomSection::first();
        $originalPath = $section->image_path;

        $newImage = UploadedFile::fake()->image('nueva.jpg', 800, 600);
        $response = $this->actingAs($admin)->put("/admin/custom-sections/{$section->id}", [
            'title' => 'Editado',
            'body' => 'Texto editado.',
            'image' => $newImage,
        ]);

        $response->assertRedirect();
        $section->refresh();
        $this->assertSame('Editado', $section->title);
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($section->image_path);
    }

    public function test_administradora_puede_ocultar_y_reactivar_una_seccion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = CustomSection::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->patch("/admin/custom-sections/{$section->id}/toggle");
        $this->assertDatabaseHas('custom_sections', ['id' => $section->id, 'is_active' => 0]);

        $this->actingAs($admin)->patch("/admin/custom-sections/{$section->id}/toggle");
        $this->assertDatabaseHas('custom_sections', ['id' => $section->id, 'is_active' => 1]);
    }

    public function test_administradora_puede_reordenar_las_secciones(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = CustomSection::factory()->create(['position' => 0]);
        $second = CustomSection::factory()->create(['position' => 1]);

        $this->actingAs($admin)->put('/admin/custom-sections-reorder', [
            'ids' => [$second->id, $first->id],
        ]);

        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_eliminar_una_seccion_borra_el_archivo_de_imagen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->image('a-borrar.jpg', 800, 600);
        $this->actingAs($admin)->post('/admin/custom-sections', [
            'title' => 'A borrar',
            'body' => 'Texto.',
            'image' => $file,
        ]);
        $section = CustomSection::first();
        $path = $section->image_path;

        $response = $this->actingAs($admin)->delete("/admin/custom-sections/{$section->id}");

        $response->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('custom_sections', ['id' => $section->id]);
    }

    public function test_las_secciones_activas_aparecen_en_la_home_antes_del_contacto(): void
    {
        CustomSection::factory()->create(['title' => 'Sección Visible', 'is_active' => true]);
        CustomSection::factory()->create(['title' => 'Sección Oculta', 'is_active' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeInOrder(['Sección Visible', 'id="contacto"'], false);
        $response->assertDontSee('Sección Oculta');
    }
}
