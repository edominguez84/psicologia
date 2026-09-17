<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendAppointmentCallRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function configureVapi(array $overrides = []): void
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

    private function makeApprovedAppointment(\Illuminate\Support\Carbon $startsAt, array $userOverrides = []): Appointment
    {
        $patient = User::factory()->create(array_merge(['role' => 'patient', 'phone_number' => '77778888'], $userOverrides));
        $slot = AppointmentSlot::create(['starts_at' => $startsAt, 'ends_at' => $startsAt->copy()->addHour(), 'is_active' => true]);

        $appointment = Appointment::create([
            'user_id' => $patient->id,
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
        ]);
        $appointment->forceFill(['status' => AppointmentStatus::Approved])->save();

        return $appointment;
    }

    public function test_no_hace_nada_si_vapi_esta_apagado(): void
    {
        $this->configureVapi(['enabled' => false]);
        $this->makeApprovedAppointment(now()->addHours(24));

        Http::fake();
        $this->artisan('appointments:call-reminders')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_llama_a_una_cita_dentro_de_la_ventana_configurada(): void
    {
        $this->configureVapi(['hours_before' => 24]);
        $appointment = $this->makeApprovedAppointment(now()->addHours(24));

        Http::fake(['api.vapi.ai/call' => Http::response(['id' => 'call-xyz'], 200)]);
        $this->artisan('appointments:call-reminders')->assertSuccessful();

        Http::assertSentCount(1);
        $appointment->refresh();
        $this->assertSame('scheduled', $appointment->vapi_call_status);
        $this->assertSame('call-xyz', $appointment->vapi_call_id);
        $this->assertNotNull($appointment->vapi_called_at);
    }

    public function test_no_llama_a_una_cita_fuera_de_la_ventana(): void
    {
        $this->configureVapi(['hours_before' => 24]);
        $this->makeApprovedAppointment(now()->addHours(72));

        Http::fake();
        $this->artisan('appointments:call-reminders')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_no_llama_dos_veces_a_la_misma_cita(): void
    {
        $this->configureVapi(['hours_before' => 24]);
        $appointment = $this->makeApprovedAppointment(now()->addHours(24));
        $appointment->forceFill(['vapi_call_status' => 'scheduled'])->save();

        Http::fake();
        $this->artisan('appointments:call-reminders')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_no_llama_una_cita_pendiente_sin_aprobar(): void
    {
        $this->configureVapi(['hours_before' => 24]);
        $patient = User::factory()->create(['role' => 'patient', 'phone_number' => '77778888']);
        $slot = AppointmentSlot::create(['starts_at' => now()->addHours(24), 'ends_at' => now()->addHours(25), 'is_active' => true]);
        Appointment::create(['user_id' => $patient->id, 'appointment_slot_id' => $slot->id, 'payment_method' => 'bank_transfer']);

        Http::fake();
        $this->artisan('appointments:call-reminders')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_marca_fallo_si_el_paciente_no_tiene_telefono(): void
    {
        $this->configureVapi(['hours_before' => 24]);
        $appointment = $this->makeApprovedAppointment(now()->addHours(24), ['phone_number' => null]);

        Http::fake();
        $this->artisan('appointments:call-reminders')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame('failed', $appointment->fresh()->vapi_call_status);
    }

    public function test_marca_fallo_si_vapi_rechaza_la_llamada(): void
    {
        $this->configureVapi(['hours_before' => 24]);
        $appointment = $this->makeApprovedAppointment(now()->addHours(24));

        Http::fake(['api.vapi.ai/call' => Http::response(['message' => 'error'], 500)]);
        $this->artisan('appointments:call-reminders')->assertSuccessful();

        $this->assertSame('failed', $appointment->fresh()->vapi_call_status);
    }
}
