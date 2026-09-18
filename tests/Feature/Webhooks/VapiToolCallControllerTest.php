<?php

namespace Tests\Feature\Webhooks;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VapiToolCallControllerTest extends TestCase
{
    use RefreshDatabase;

    private function configureVoiceRegistration(): void
    {
        app(SiteSettingsService::class)->set('voice_registration', [
            'enabled' => true,
            'assistant_id' => 'assistant-voice-registration',
            'webhook_secret' => 'voice-secret-abc',
        ]);
    }

    private function toolCallPayload(string $toolCallId, string $functionName, array $arguments): array
    {
        return [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [
                    [
                        'id' => $toolCallId,
                        'function' => [
                            'name' => $functionName,
                            'arguments' => $arguments,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_rechaza_una_tool_call_con_secreto_invalido(): void
    {
        $this->configureVoiceRegistration();

        $response = $this->postJson('/webhooks/vapi-tools', $this->toolCallPayload('call-1', 'create_patient_account', [
            'name' => 'Juan Pérez', 'email' => 'juan@example.com', 'phone_number' => '77778888',
        ]), ['X-Vapi-Secret' => 'secreto-incorrecto']);

        $response->assertStatus(401);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_crea_la_cuenta_de_paciente_y_envia_el_correo(): void
    {
        $this->configureVoiceRegistration();
        Mail::fake();

        $response = $this->postJson('/webhooks/vapi-tools', $this->toolCallPayload('call-1', 'create_patient_account', [
            'name' => 'Juan Pérez', 'email' => 'juan@example.com', 'phone_number' => '77778888',
        ]), ['X-Vapi-Secret' => 'voice-secret-abc']);

        $response->assertOk();
        $response->assertJson(fn ($json) => $json->has('results.0.toolCallId')
            ->where('results.0.toolCallId', 'call-1')
            ->etc());

        $user = User::where('email', 'juan@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('patient', $user->role);
        $this->assertSame('Juan Pérez', $user->name);
        $this->assertSame('77778888', $user->phone_number);

        Mail::assertSent(\App\Mail\AccountCreatedByVoiceCall::class, fn ($mail) => $mail->user->is($user));
        $this->assertDatabaseHas('admin_notifications', ['type' => 'voice_account_created']);
    }

    public function test_no_duplica_la_cuenta_si_ya_existe_una_con_ese_correo(): void
    {
        $this->configureVoiceRegistration();
        Mail::fake();
        User::factory()->create(['email' => 'juan@example.com', 'role' => 'patient']);

        $response = $this->postJson('/webhooks/vapi-tools', $this->toolCallPayload('call-1', 'create_patient_account', [
            'name' => 'Juan Pérez', 'email' => 'juan@example.com', 'phone_number' => '77778888',
        ]), ['X-Vapi-Secret' => 'voice-secret-abc']);

        $response->assertOk();
        $this->assertDatabaseCount('users', 1);
        Mail::assertNothingSent();
    }

    public function test_no_duplica_la_cuenta_si_ya_existe_una_con_ese_telefono(): void
    {
        $this->configureVoiceRegistration();
        Mail::fake();
        User::factory()->create(['phone_number' => '77778888', 'role' => 'patient']);

        $response = $this->postJson('/webhooks/vapi-tools', $this->toolCallPayload('call-1', 'create_patient_account', [
            'name' => 'Juan Pérez', 'email' => 'nuevo@example.com', 'phone_number' => '77778888',
        ]), ['X-Vapi-Secret' => 'voice-secret-abc']);

        $response->assertOk();
        $this->assertDatabaseCount('users', 1);
        Mail::assertNothingSent();
    }

    public function test_pide_los_datos_de_nuevo_si_faltan_o_son_invalidos(): void
    {
        $this->configureVoiceRegistration();
        Mail::fake();

        $response = $this->postJson('/webhooks/vapi-tools', $this->toolCallPayload('call-1', 'create_patient_account', [
            'name' => '', 'email' => 'correo-invalido', 'phone_number' => '77778888',
        ]), ['X-Vapi-Secret' => 'voice-secret-abc']);

        $response->assertOk();
        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingSent();
    }

    public function test_responde_con_error_generico_ante_una_funcion_desconocida(): void
    {
        $this->configureVoiceRegistration();

        $response = $this->postJson('/webhooks/vapi-tools', $this->toolCallPayload('call-1', 'funcion_inexistente', []), [
            'X-Vapi-Secret' => 'voice-secret-abc',
        ]);

        $response->assertOk();
        $response->assertJson(fn ($json) => $json->has('results.0.toolCallId')
            ->where('results.0.toolCallId', 'call-1')
            ->etc());
        $this->assertDatabaseCount('users', 0);
    }
}
