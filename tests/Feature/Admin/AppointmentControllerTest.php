<?php

namespace Tests\Feature\Admin;

use App\Mail\AppointmentDecided;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_listado(): void
    {
        $this->get('/admin/appointments')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/appointments')->assertForbidden();
    }

    public function test_admin_puede_aprobar_una_cita_y_se_notifica_al_paciente(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $appointment = Appointment::factory()->create();

        $response = $this->actingAs($admin)->patch("/admin/appointments/{$appointment->id}/approve");

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertSame('approved', $appointment->status->value);
        $this->assertNotNull($appointment->decided_at);
        $this->assertSame($admin->id, $appointment->decided_by);
        Mail::assertSent(AppointmentDecided::class, fn ($mail) => $mail->hasTo($appointment->user->email));
    }

    public function test_super_admin_tambien_puede_aprobar_citas(): void
    {
        Mail::fake();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $appointment = Appointment::factory()->create();

        $this->actingAs($superAdmin)->patch("/admin/appointments/{$appointment->id}/approve")->assertRedirect();

        $this->assertSame('approved', $appointment->fresh()->status->value);
    }

    public function test_admin_puede_rechazar_una_cita_y_se_notifica_al_paciente(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $appointment = Appointment::factory()->create();

        $response = $this->actingAs($admin)->patch("/admin/appointments/{$appointment->id}/reject", [
            'admin_note' => 'No tengo disponibilidad ese día.',
        ]);

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertSame('rejected', $appointment->status->value);
        Mail::assertSent(AppointmentDecided::class);
    }

    public function test_el_listado_muestra_citas_en_cualquier_estado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Appointment::factory()->create(['status' => 'pending']);
        Appointment::factory()->create(['status' => 'approved']);
        Appointment::factory()->create(['status' => 'rejected']);

        $response = $this->actingAs($admin)->get('/admin/appointments');

        $response->assertOk();
        $response->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 3);
    }

    public function test_admin_puede_confirmar_el_pago_de_una_cita_avisada(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $appointment = Appointment::factory()->create(['payment_method' => 'bank_transfer']);
        $appointment->forceFill(['payment_status' => 'reported'])->save();

        $response = $this->actingAs($admin)->patch("/admin/appointments/{$appointment->id}/confirm-payment");

        $response->assertRedirect();
        $this->assertSame('confirmed', $appointment->fresh()->payment_status);
    }
}
