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
            'rating' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('testimonials', [
            'user_id' => $patient->id,
            'text' => 'Mi experiencia fue muy buena.',
            'rating' => 5,
            'is_approved' => 0,
        ]);
        $this->assertDatabaseHas('admin_notifications', ['type' => 'testimonial_pending', 'feature' => 'testimonials']);
    }

    public function test_requiere_una_calificacion_entre_1_y_5(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($patient)->put('/perfil/testimonio', [
            'text' => 'Texto válido.',
            'rating' => 6,
        ]);

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_un_segundo_envio_actualiza_el_mismo_testimonio_en_vez_de_crear_otro(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $this->actingAs($patient)->put('/perfil/testimonio', ['text' => 'Primera versión.', 'rating' => 4]);
        $this->actingAs($patient)->put('/perfil/testimonio', ['text' => 'Segunda versión.', 'rating' => 5]);

        $this->assertDatabaseCount('testimonials', 1);
        $this->assertDatabaseHas('testimonials', ['user_id' => $patient->id, 'text' => 'Segunda versión.', 'rating' => 5]);
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

        $this->actingAs($patient)->put('/perfil/testimonio', ['text' => 'Versión editada.', 'rating' => 5]);

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

    public function test_solo_super_admin_puede_aprobar_testimonios(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $testimonial = Testimonial::factory()->create();

        $this->actingAs($admin)
            ->patch("/admin/testimonials/{$testimonial->id}/approve")
            ->assertForbidden();
    }

    public function test_un_testimonio_con_lenguaje_prohibido_se_rechaza_sin_guardarse(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($patient)->put('/perfil/testimonio', [
            'text' => 'Esta psicóloga es una mierda.',
            'rating' => 1,
        ]);

        $response->assertSessionHasErrors('text');
        $this->assertDatabaseCount('testimonials', 0);
        $this->assertSame(1, $patient->fresh()->profanity_strikes);
    }

    public function test_al_llegar_al_umbral_configurado_la_cuenta_se_banea_automaticamente(): void
    {
        app(\App\Services\SiteSettingsService::class)->set('profanity_filter', ['extra_words' => [], 'ban_threshold' => 2]);
        $patient = User::factory()->create(['role' => 'patient']);

        $this->actingAs($patient)->put('/perfil/testimonio', ['text' => 'Eres un idiota.', 'rating' => 1]);
        $response = $this->actingAs($patient)->put('/perfil/testimonio', ['text' => 'Puta mierda de servicio.', 'rating' => 1]);

        $patient->refresh();
        $this->assertSame(2, $patient->profanity_strikes);
        $this->assertTrue($patient->isBanned());
        $response->assertRedirect(route('login'));
    }
}
