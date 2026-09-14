<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_informe(): void
    {
        $this->get('/admin/reports')->assertRedirect(route('login'));
    }

    public function test_administradora_no_super_recibe_403(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/reports')->assertForbidden();
    }

    public function test_super_admin_puede_ver_el_informe_con_las_metricas(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        Appointment::factory()->create(['status' => 'pending']);
        ContactMessage::create([
            'name' => 'Ana', 'email' => 'ana@example.com', 'message' => 'Consulta de prueba.',
        ]);
        Testimonial::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($superAdmin)->get('/admin/reports');

        $response->assertOk();
        $response->assertViewHas('appointmentsByStatus', fn ($data) => $data['Pendiente'] === 1);
        $response->assertViewHas('unhandledContacts', 1);
        $response->assertViewHas('pendingTestimonials', 1);
    }

    public function test_administradora_no_super_no_puede_descargar_el_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/reports/download')->assertForbidden();
    }

    public function test_super_admin_puede_descargar_el_pdf(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get('/admin/reports/download');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
