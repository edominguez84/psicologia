<?php

namespace Tests\Feature\Admin;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_listado(): void
    {
        $this->get('/admin/testimonials')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/testimonials')->assertForbidden();
    }

    public function test_administradora_no_super_recibe_403(): void
    {
        // La aprobación de testimonios es exclusiva de super_admin —
        // confirmado explícitamente por el usuario: "deberán ser aprobados
        // por el super usuario".
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/testimonials')->assertForbidden();
    }

    public function test_super_admin_puede_aprobar_un_testimonio(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $testimonial = Testimonial::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($superAdmin)->patch("/admin/testimonials/{$testimonial->id}/approve");

        $response->assertRedirect();
        $testimonial->refresh();
        $this->assertTrue($testimonial->is_approved);
        $this->assertNotNull($testimonial->approved_at);
        $this->assertSame($superAdmin->id, $testimonial->approved_by);
    }

    public function test_super_admin_puede_ocultar_un_testimonio_aprobado(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $testimonial = Testimonial::factory()->create(['is_approved' => true, 'approved_at' => now()]);

        $response = $this->actingAs($superAdmin)->patch("/admin/testimonials/{$testimonial->id}/unapprove");

        $response->assertRedirect();
        $this->assertFalse($testimonial->fresh()->is_approved);
    }

    public function test_super_admin_puede_eliminar_un_testimonio(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $testimonial = Testimonial::factory()->create();

        $response = $this->actingAs($superAdmin)->delete("/admin/testimonials/{$testimonial->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_la_home_solo_muestra_testimonios_aprobados(): void
    {
        Testimonial::factory()->create(['is_approved' => true, 'text' => 'Testimonio aprobado visible']);
        Testimonial::factory()->create(['is_approved' => false, 'text' => 'Testimonio pendiente oculto']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Testimonio aprobado visible');
        $response->assertDontSee('Testimonio pendiente oculto');
    }

    public function test_la_home_dibuja_las_estrellas_reales_del_testimonio_aprobado(): void
    {
        Testimonial::factory()->create(['is_approved' => true, 'text' => 'Excelente atención', 'rating' => 3]);

        $response = $this->get('/');

        $response->assertOk();
        // 3 estrellas rellenas para este testimonio (path con "M12 2l3 6.9...").
        $filledStars = substr_count($response->getContent(), 'M12 2l3 6.9 7.5.6-5.7 5 1.8 7.4L12 17.8 5.4 21.9 7.2 14.5 1.5 9.5 9 8.9z');
        $this->assertGreaterThanOrEqual(3, $filledStars);
    }
}
