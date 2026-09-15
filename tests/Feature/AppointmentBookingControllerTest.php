<?php

namespace Tests\Feature;

use App\Mail\AppointmentRequested;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Promotion;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentBookingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_sus_citas(): void
    {
        $this->get('/perfil/citas')->assertRedirect(route('login'));
    }

    public function test_un_paciente_solo_ve_horarios_libres_no_los_ya_tomados(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $freeSlot = AppointmentSlot::factory()->create();
        $takenSlot = AppointmentSlot::factory()->create();
        Appointment::factory()->create(['appointment_slot_id' => $takenSlot->id, 'status' => 'pending']);

        $response = $this->actingAs($patient)->get('/perfil/citas');

        $response->assertOk();
        $response->assertViewHas('availableSlots', function ($slots) use ($freeSlot, $takenSlot) {
            return $slots->pluck('id')->contains($freeSlot->id)
                && ! $slots->pluck('id')->contains($takenSlot->id);
        });
    }

    public function test_un_slot_con_cita_rechazada_o_cancelada_vuelve_a_estar_disponible(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();
        Appointment::factory()->create(['appointment_slot_id' => $slot->id, 'status' => 'rejected']);

        $response = $this->actingAs($patient)->get('/perfil/citas');

        $response->assertViewHas('availableSlots', fn ($slots) => $slots->pluck('id')->contains($slot->id));
    }

    public function test_solicitar_una_cita_notifica_a_todos_los_super_admins(): void
    {
        Mail::fake();
        $patient = User::factory()->create(['role' => 'patient']);
        $superAdmin1 = User::factory()->create(['role' => 'super_admin', 'email' => 'super1@example.com']);
        $superAdmin2 = User::factory()->create(['role' => 'super_admin', 'email' => 'super2@example.com']);
        $slot = AppointmentSlot::factory()->create();

        $response = $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'user_id' => $patient->id,
            'appointment_slot_id' => $slot->id,
            'status' => 'pending',
        ]);
        Mail::assertSent(AppointmentRequested::class, function ($mail) use ($superAdmin1, $superAdmin2) {
            return $mail->hasTo($superAdmin1->email) && $mail->hasCc($superAdmin2->email);
        });
    }

    public function test_no_puede_solicitar_un_horario_ya_tomado(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();
        Appointment::factory()->create(['appointment_slot_id' => $slot->id, 'status' => 'approved']);

        $response = $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertSessionHasErrors('appointment_slot_id');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_user_id_de_la_cita_siempre_es_el_del_autenticado_nunca_del_request(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $otherPatient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();

        $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
            'user_id' => $otherPatient->id,
        ]);

        $this->assertDatabaseHas('appointments', ['appointment_slot_id' => $slot->id, 'user_id' => $patient->id]);
        $this->assertDatabaseMissing('appointments', ['appointment_slot_id' => $slot->id, 'user_id' => $otherPatient->id]);
    }

    public function test_un_paciente_no_puede_cancelar_la_cita_de_otro(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $otherPatient = User::factory()->create(['role' => 'patient']);
        $appointment = Appointment::factory()->create(['user_id' => $otherPatient->id, 'status' => 'pending']);

        $this->actingAs($patient)
            ->delete("/perfil/citas/{$appointment->id}")
            ->assertForbidden();

        $this->assertSame('pending', $appointment->fresh()->status->value);
    }

    public function test_un_paciente_puede_cancelar_su_propia_cita_pendiente(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $appointment = Appointment::factory()->create(['user_id' => $patient->id, 'status' => 'pending']);

        $response = $this->actingAs($patient)->delete("/perfil/citas/{$appointment->id}");

        $response->assertRedirect();
        $this->assertSame('cancelled', $appointment->fresh()->status->value);
    }

    public function test_no_puede_cancelar_una_cita_ya_aprobada(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $appointment = Appointment::factory()->create(['user_id' => $patient->id, 'status' => 'approved']);

        $response = $this->actingAs($patient)->delete("/perfil/citas/{$appointment->id}");

        $response->assertSessionHasErrors('appointment');
        $this->assertSame('approved', $appointment->fresh()->status->value);
    }

    public function test_el_paciente_ve_todas_sus_citas_en_cualquier_estado(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        Appointment::factory()->create(['user_id' => $patient->id, 'status' => 'pending']);
        Appointment::factory()->create(['user_id' => $patient->id, 'status' => 'approved']);
        Appointment::factory()->create(['user_id' => $patient->id, 'status' => 'rejected']);

        $response = $this->actingAs($patient)->get('/perfil/citas');

        $response->assertOk();
        $response->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 3);
    }

    public function test_pagar_por_transferencia_marca_la_cita_como_avisada(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();

        $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertDatabaseHas('appointments', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'reported',
        ]);
    }

    public function test_elegir_una_promocion_activa_refleja_su_precio_en_la_cita(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();
        $promotion = \App\Models\Promotion::create([
            'title' => 'Paquete inicial', 'price' => 45, 'description' => 'x', 'is_active' => true,
        ]);

        $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
            'promotion_id' => $promotion->id,
        ]);

        $this->assertDatabaseHas('appointments', [
            'appointment_slot_id' => $slot->id,
            'promotion_id' => $promotion->id,
            'amount' => 45.00,
        ]);
    }

    public function test_rechaza_una_promocion_inactiva(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();
        $promotion = \App\Models\Promotion::create([
            'title' => 'Vieja', 'price' => 45, 'description' => 'x', 'is_active' => false,
        ]);

        $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
            'promotion_id' => $promotion->id,
        ]);

        $this->assertDatabaseHas('appointments', [
            'appointment_slot_id' => $slot->id,
            'promotion_id' => null,
            'amount' => null,
        ]);
    }

    public function test_requiere_un_metodo_de_pago(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();

        $response = $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
        ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_pagar_con_wompi_redirige_al_enlace_de_pago_generado(): void
    {
        app(SiteSettingsService::class)->set('payment', [
            'method' => 'wompi',
            'wompi' => ['mode' => 'sandbox', 'app_id' => 'test-app', 'api_secret' => 'test-secret'],
        ]);
        Http::fake([
            'id.wompi.sv/*' => Http::response(['access_token' => 'tok'], 200),
            'api.wompi.sv/EnlacePago' => Http::response([
                'idEnlace' => 555, 'urlEnlace' => 'https://checkout.wompi.sv/enlace/555',
            ], 200),
        ]);
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();
        $promotion = Promotion::create(['title' => 'Plan', 'price' => 45, 'description' => 'x', 'is_active' => true]);

        $response = $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'wompi',
            'promotion_id' => $promotion->id,
        ]);

        $response->assertRedirect('https://checkout.wompi.sv/enlace/555');
        $this->assertDatabaseHas('appointments', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'wompi',
            'payment_reference' => '555',
        ]);
    }

    public function test_pagar_con_wompi_sin_credenciales_configuradas_no_pierde_la_cita(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();
        $promotion = Promotion::create(['title' => 'Plan', 'price' => 45, 'description' => 'x', 'is_active' => true]);

        $response = $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'wompi',
            'promotion_id' => $promotion->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'wompi',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_si_wompi_falla_al_generar_el_enlace_la_cita_sigue_creada(): void
    {
        app(SiteSettingsService::class)->set('payment', [
            'method' => 'wompi',
            'wompi' => ['mode' => 'sandbox', 'app_id' => 'test-app', 'api_secret' => 'test-secret'],
        ]);
        Http::fake([
            'id.wompi.sv/*' => Http::response(['error' => 'invalid_client'], 400),
        ]);
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();
        $promotion = Promotion::create(['title' => 'Plan', 'price' => 45, 'description' => 'x', 'is_active' => true]);

        $response = $this->actingAs($patient)->post('/perfil/citas', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'wompi',
            'promotion_id' => $promotion->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'wompi',
            'payment_status' => 'unpaid',
        ]);
    }
}
