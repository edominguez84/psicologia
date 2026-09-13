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

    public function test_administradora_puede_aprobar_un_testimonio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $testimonial = Testimonial::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($admin)->patch("/admin/testimonials/{$testimonial->id}/approve");

        $response->assertRedirect();
        $testimonial->refresh();
        $this->assertTrue($testimonial->is_approved);
        $this->assertNotNull($testimonial->approved_at);
        $this->assertSame($admin->id, $testimonial->approved_by);
    }

    public function test_administradora_puede_ocultar_un_testimonio_aprobado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $testimonial = Testimonial::factory()->create(['is_approved' => true, 'approved_at' => now()]);

        $response = $this->actingAs($admin)->patch("/admin/testimonials/{$testimonial->id}/unapprove");

        $response->assertRedirect();
        $this->assertFalse($testimonial->fresh()->is_approved);
    }

    public function test_administradora_puede_eliminar_un_testimonio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $testimonial = Testimonial::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/testimonials/{$testimonial->id}");

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
}
