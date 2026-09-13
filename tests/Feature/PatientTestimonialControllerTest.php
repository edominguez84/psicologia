<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTestimonialControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_formulario(): void
    {
        $this->get('/perfil/testimonio')->assertRedirect(route('login'));
    }

    public function test_un_paciente_puede_crear_su_testimonio(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($patient)->put('/perfil/testimonio', [
            'text' => 'Mi experiencia fue muy buena.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('testimonials', [
            'user_id' => $patient->id,
            'text' => 'Mi experiencia fue muy buena.',
            'is_approved' => 0,
        ]);
    }

    public function test_un_segundo_envio_actualiza_el_mismo_testimonio_en_vez_de_crear_otro(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $this->actingAs($patient)->put('/perfil/testimonio', ['text' => 'Primera versión.']);
        $this->actingAs($patient)->put('/perfil/testimonio', ['text' => 'Segunda versión.']);

        $this->assertDatabaseCount('testimonials', 1);
        $this->assertDatabaseHas('testimonials', ['user_id' => $patient->id, 'text' => 'Segunda versión.']);
    }

    public function test_editar_un_testimonio_aprobado_lo_vuelve_a_dejar_pendiente(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $admin = User::factory()->create(['role' => 'admin']);
        $testimonial = Testimonial::factory()->create([
            'user_id' => $patient->id,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        $this->actingAs($patient)->put('/perfil/testimonio', ['text' => 'Versión editada.']);

        $testimonial->refresh();
        $this->assertFalse($testimonial->is_approved);
        $this->assertNull($testimonial->approved_at);
        $this->assertNull($testimonial->approved_by);
    }

    public function test_un_paciente_no_puede_aprobar_su_propio_testimonio(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $testimonial = Testimonial::factory()->create(['user_id' => $patient->id]);

        $this->actingAs($patient)
            ->patch("/admin/testimonials/{$testimonial->id}/approve")
            ->assertForbidden();

        $this->assertFalse($testimonial->fresh()->is_approved);
    }
}
