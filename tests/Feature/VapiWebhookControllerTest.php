<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VapiWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SiteSettingsService::class)->set('vapi', ['webhook_secret' => 'secret-abc']);
    }

    private function makeAppointment(): Appointment
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'is_active' => true]);

        return Appointment::create(['user_id' => $patient->id, 'appointment_slot_id' => $slot->id, 'payment_method' => 'bank_transfer']);
    }

    private function postWebhook(array $payload, ?string $secret = 'secret-abc'): \Illuminate\Testing\TestResponse
    {
        return $this->call('POST', '/webhooks/vapi', [], [], [], array_filter([
            'HTTP_X_Vapi_Secret' => $secret,
            'CONTENT_TYPE' => 'application/json',
        ]), json_encode($payload));
    }

    public function test_rechaza_un_webhook_con_secreto_invalido(): void
    {
        $response = $this->postWebhook(['message' => ['type' => 'end-of-call-report']], secret: 'incorrecto');

        $response->assertStatus(401);
    }

    public function test_registra_el_resultado_de_la_llamada(): void
    {
        $appointment = $this->makeAppointment();

        $response = $this->postWebhook([
            'message' => [
                'type' => 'end-of-call-report',
                'endedReason' => 'customer-ended-call',
                'summary' => 'El paciente confirmó su asistencia.',
                'call' => ['metadata' => ['appointment_id' => $appointment->id]],
                'artifact' => ['transcript' => 'Hola... Sí, confirmo.'],
            ],
        ]);

        $response->assertOk();
        $appointment->refresh();
        $this->assertSame('completed', $appointment->vapi_call_status);
        $this->assertSame('customer-ended-call', $appointment->vapi_call_result['ended_reason']);
        $this->assertSame('El paciente confirmó su asistencia.', $appointment->vapi_call_result['summary']);
    }

    public function test_ignora_un_webhook_sin_cita_asociada(): void
    {
        $response = $this->postWebhook([
            'message' => ['type' => 'end-of-call-report', 'call' => ['metadata' => ['appointment_id' => 999999]]],
        ]);

        $response->assertOk();
    }

    public function test_ignora_eventos_que_no_son_end_of_call_report(): void
    {
        $appointment = $this->makeAppointment();

        $response = $this->postWebhook([
            'message' => ['type' => 'status-update', 'call' => ['metadata' => ['appointment_id' => $appointment->id]]],
        ]);

        $response->assertOk();
        $this->assertNull($appointment->fresh()->vapi_call_status);
    }

    public function test_no_cambia_el_status_de_la_cita_solo_el_resultado_de_la_llamada(): void
    {
        $appointment = $this->makeAppointment();
        $appointment->forceFill(['status' => \App\Enums\AppointmentStatus::Approved])->save();

        $this->postWebhook([
            'message' => ['type' => 'end-of-call-report', 'call' => ['metadata' => ['appointment_id' => $appointment->id]]],
        ]);

        // La llamada es un recordatorio de cortesía — jamás cambia el status
        // de la cita, ya decidido por el super_admin.
        $this->assertSame('approved', $appointment->fresh()->status->value);
    }
}
