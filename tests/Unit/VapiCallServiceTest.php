<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\User;
use App\Services\SiteSettingsService;
use App\Services\VapiCallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class VapiCallServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configureCredentials(array $overrides = []): void
    {
        app(SiteSettingsService::class)->set('vapi', array_merge([
            'enabled' => true,
            'api_key' => 'vapi-test-key',
            'assistant_id' => 'assistant-123',
            'phone_number_id' => 'phone-456',
            'webhook_secret' => 'secret-abc',
            'hours_before' => 24,
        ], $overrides));
    }

    public function test_no_esta_configurado_sin_credenciales(): void
    {
        $this->assertFalse(app(VapiCallService::class)->isConfigured());
    }

    public function test_esta_configurado_con_las_tres_credenciales(): void
    {
        $this->configureCredentials();

        $this->assertTrue(app(VapiCallService::class)->isConfigured());
    }

    public function test_no_es_usable_si_esta_apagado_aunque_haya_credenciales(): void
    {
        $this->configureCredentials(['enabled' => false]);

        $this->assertFalse(app(VapiCallService::class)->isUsable());
    }

    public function test_es_usable_activado_y_configurado(): void
    {
        $this->configureCredentials();

        $this->assertTrue(app(VapiCallService::class)->isUsable());
    }

    public function test_hours_before_devuelve_el_valor_configurado(): void
    {
        $this->configureCredentials(['hours_before' => 48]);

        $this->assertSame(48, app(VapiCallService::class)->hoursBefore());
    }

    public function test_call_for_appointment_envia_las_variables_correctas(): void
    {
        $this->configureCredentials();
        $patient = User::factory()->create(['name' => 'Elian Domínguez', 'phone_number' => '77778888', 'role' => 'patient']);
        $slot = AppointmentSlot::create(['starts_at' => now()->addDay()->setTime(14, 0), 'ends_at' => now()->addDay()->setTime(15, 0), 'is_active' => true]);
        $appointment = Appointment::create(['user_id' => $patient->id, 'appointment_slot_id' => $slot->id, 'payment_method' => 'bank_transfer'])
            ->load(['user', 'appointmentSlot']);

        Http::fake(['api.vapi.ai/call' => Http::response(['id' => 'call-xyz'], 200)]);

        $callId = app(VapiCallService::class)->callForAppointment($appointment);

        $this->assertSame('call-xyz', $callId);
        Http::assertSent(function ($request) use ($appointment) {
            return $request->url() === 'https://api.vapi.ai/call'
                && $request['assistantId'] === 'assistant-123'
                && $request['phoneNumberId'] === 'phone-456'
                && $request['customer']['number'] === '+50377778888'
                && $request['assistantOverrides']['variableValues']['nombrePaciente'] === 'Elian Domínguez'
                && $request['metadata']['appointment_id'] === $appointment->id;
        });
    }

    public function test_respeta_un_numero_que_ya_trae_codigo_de_pais(): void
    {
        $this->configureCredentials();
        $patient = User::factory()->create(['phone_number' => '+50177778888', 'role' => 'patient']);
        $slot = AppointmentSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'is_active' => true]);
        $appointment = Appointment::create(['user_id' => $patient->id, 'appointment_slot_id' => $slot->id, 'payment_method' => 'bank_transfer'])
            ->load(['user', 'appointmentSlot']);

        Http::fake(['api.vapi.ai/call' => Http::response(['id' => 'call-xyz'], 200)]);

        app(VapiCallService::class)->callForAppointment($appointment);

        Http::assertSent(fn ($request) => $request['customer']['number'] === '+50177778888');
    }

    public function test_lanza_excepcion_si_vapi_rechaza_la_solicitud(): void
    {
        $this->configureCredentials();
        $patient = User::factory()->create(['phone_number' => '77778888', 'role' => 'patient']);
        $slot = AppointmentSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'is_active' => true]);
        $appointment = Appointment::create(['user_id' => $patient->id, 'appointment_slot_id' => $slot->id, 'payment_method' => 'bank_transfer'])
            ->load(['user', 'appointmentSlot']);

        Http::fake(['api.vapi.ai/call' => Http::response(['message' => 'invalid assistant'], 400)]);

        $this->expectException(RuntimeException::class);

        app(VapiCallService::class)->callForAppointment($appointment);
    }

    public function test_verifica_el_secreto_del_webhook(): void
    {
        $this->configureCredentials();
        $service = app(VapiCallService::class);

        $this->assertTrue($service->verifyWebhookSecret('secret-abc'));
        $this->assertFalse($service->verifyWebhookSecret('otro-valor'));
        $this->assertFalse($service->verifyWebhookSecret(null));
    }

    /**
     * callRaw() — usado por el botón de "llamada de prueba" del panel, que
     * permite escribir un nombre y teléfono cualquiera sin depender de que
     * exista una cuenta/cita real con ese teléfono cargado.
     */
    public function test_call_raw_dispara_la_llamada_sin_necesitar_appointment_ni_user(): void
    {
        $this->configureCredentials();
        Http::fake(['api.vapi.ai/call' => Http::response(['id' => 'call-raw-1'], 200)]);

        $callId = app(VapiCallService::class)->callRaw(
            phoneNumber: '77778888',
            patientName: 'Juan Pérez',
            appointmentTime: 'lunes 21 de septiembre a las 10:00 AM',
        );

        $this->assertSame('call-raw-1', $callId);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.vapi.ai/call'
            && $request['customer']['number'] === '+50377778888'
            && $request['assistantOverrides']['variableValues']['nombrePaciente'] === 'Juan Pérez'
            && $request['assistantOverrides']['variableValues']['horarioCita'] === 'lunes 21 de septiembre a las 10:00 AM');
    }

    public function test_call_raw_lanza_excepcion_si_no_esta_configurado(): void
    {
        $this->expectException(RuntimeException::class);

        app(VapiCallService::class)->callRaw('77778888', 'Juan Pérez', 'mañana');
    }
}
