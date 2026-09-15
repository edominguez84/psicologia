<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WompiWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private const API_SECRET = 'test-api-secret';

    protected function setUp(): void
    {
        parent::setUp();
        app(SiteSettingsService::class)->set('payment', [
            'method' => 'wompi',
            'wompi' => ['mode' => 'sandbox', 'app_id' => 'test-app-id', 'api_secret' => self::API_SECRET],
        ]);
    }

    private function appointmentWithReference(string $reference): Appointment
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();

        $appointment = Appointment::create([
            'user_id' => $patient->id,
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'wompi',
            'amount' => 50,
        ]);
        $appointment->forceFill(['payment_reference' => $reference, 'payment_status' => 'unpaid'])->save();

        return $appointment;
    }

    private function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, self::API_SECRET);

        return $this->call('POST', '/webhooks/wompi', [], [], [], [
            'HTTP_wompi_hash' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_confirma_la_cita_cuando_el_pago_es_exitoso(): void
    {
        $appointment = $this->appointmentWithReference('123');

        $response = $this->postWebhook([
            'ResultadoTransaccion' => 'ExitosaAprobada',
            'EnlacePago' => ['idEnlace' => 123],
        ]);

        $response->assertOk();
        $this->assertSame('confirmed', $appointment->fresh()->payment_status);
    }

    public function test_no_confirma_si_el_resultado_no_es_exitoso(): void
    {
        $appointment = $this->appointmentWithReference('124');

        $this->postWebhook([
            'ResultadoTransaccion' => 'Rechazada',
            'EnlacePago' => ['idEnlace' => 124],
        ]);

        $this->assertSame('unpaid', $appointment->fresh()->payment_status);
    }

    public function test_rechaza_un_webhook_con_firma_invalida(): void
    {
        $appointment = $this->appointmentWithReference('125');

        $response = $this->call('POST', '/webhooks/wompi', [], [], [], [
            'HTTP_wompi_hash' => 'firma-falsa',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['ResultadoTransaccion' => 'ExitosaAprobada', 'EnlacePago' => ['idEnlace' => 125]]));

        $response->assertStatus(401);
        $this->assertSame('unpaid', $appointment->fresh()->payment_status);
    }

    public function test_es_idempotente_si_ya_estaba_confirmada(): void
    {
        $appointment = $this->appointmentWithReference('126');
        $appointment->forceFill(['payment_status' => 'confirmed'])->save();

        $response = $this->postWebhook([
            'ResultadoTransaccion' => 'Rechazada',
            'EnlacePago' => ['idEnlace' => 126],
        ]);

        $response->assertOk();
        // Un evento tardío/duplicado no revierte una cita ya confirmada.
        $this->assertSame('confirmed', $appointment->fresh()->payment_status);
    }

    public function test_ignora_un_webhook_sin_cita_asociada(): void
    {
        $response = $this->postWebhook([
            'ResultadoTransaccion' => 'ExitosaAprobada',
            'EnlacePago' => ['idEnlace' => 999999],
        ]);

        $response->assertOk();
    }
}
