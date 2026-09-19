<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCode;
use App\Models\User;
use App\Services\TwoFactorChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_correcto_envia_un_codigo_por_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        Mail::assertSent(LoginCode::class);
    }

    public function test_codigo_correcto_autentica(): void
    {
        Mail::fake();
        $user = User::factory()->create(['role' => 'admin']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $sent = null;
        Mail::assertSent(LoginCode::class, function ($mail) use (&$sent) {
            $sent = $mail->code;

            return true;
        });

        $response = $this->post('/2fa/verify', ['code' => $sent]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_codigo_incorrecto_no_autentica(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->post('/2fa/verify', ['code' => '000000']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
    }

    public function test_codigo_expirado_no_autentica_y_no_reenvia_automaticamente(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        // Forzar la expiración del código recién generado.
        $user->loginCodes()->update(['expires_at' => now()->subMinute()]);

        $response = $this->post('/2fa/verify', ['code' => '123456']);

        $this->assertGuest();
        $response->assertSessionHasErrors('code');
        // Ya no se reenvía solo: el sistema no manda un segundo código sin
        // que la persona pulse "Reenviar código" explícitamente.
        Mail::assertSent(LoginCode::class, 1);
    }

    public function test_el_boton_reenviar_codigo_envia_uno_nuevo(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->post('/2fa/resend');

        $response->assertRedirect();
        Mail::assertSent(LoginCode::class, 2);
    }

    public function test_metodo_totp_no_envia_email_y_verifica_con_el_secreto(): void
    {
        Mail::fake();
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create([
            'role' => 'admin',
            'two_factor_method' => 'totp',
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        Mail::assertNotSent(LoginCode::class);

        $code = (new Google2FA())->getCurrentOtp($secret);
        $response = $this->post('/2fa/verify', ['code' => $code]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_sin_sesion_pendiente_redirige_a_login(): void
    {
        $response = $this->get('/2fa/challenge');

        $response->assertRedirect(route('login'));
    }

    public function test_un_paciente_tras_verificar_va_a_su_perfil_no_al_panel_admin(): void
    {
        Mail::fake();
        $patient = User::factory()->create(['role' => 'patient']);
        $this->post('/login', ['email' => $patient->email, 'password' => 'password']);

        $sent = null;
        Mail::assertSent(LoginCode::class, function ($mail) use (&$sent) {
            $sent = $mail->code;

            return true;
        });

        $response = $this->post('/2fa/verify', ['code' => $sent]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('patient.profile.edit', absolute: false));
    }

    private function configureTwilioCredentials(): void
    {
        config([
            'services.twilio.sid' => 'AC-test-sid',
            'services.twilio.token' => 'test-token',
            'services.twilio.from' => '+14483332827',
        ]);
    }

    public function test_metodo_sms_con_twilio_exitoso_no_envia_correo_y_guarda_el_canal_sms(): void
    {
        Mail::fake();
        $this->configureTwilioCredentials();
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201)]);

        $user = User::factory()->create(['two_factor_method' => 'sms', 'phone_number' => '77778888']);

        app(TwoFactorChallengeService::class)->issueChallenge($user);

        Mail::assertNotSent(LoginCode::class);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC-test-sid/Messages.json'
            && $request['To'] === '+50377778888');
        $this->assertSame('sms', $user->loginCodes()->latest('id')->first()->channel);
    }

    public function test_metodo_sms_con_twilio_fallando_cae_a_email_y_actualiza_el_canal(): void
    {
        Log::spy();
        Mail::fake();
        $this->configureTwilioCredentials();
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'The number is unverified'], 400)]);

        $user = User::factory()->create(['two_factor_method' => 'sms', 'phone_number' => '77778888']);

        app(TwoFactorChallengeService::class)->issueChallenge($user);

        Mail::assertSent(LoginCode::class);
        $this->assertSame('email', $user->loginCodes()->latest('id')->first()->channel);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => str_contains($message, 'fallo enviando SMS'))
            ->once();
    }

    public function test_metodo_sms_sin_credenciales_de_twilio_sigue_cayendo_a_email_como_antes(): void
    {
        Mail::fake();
        config([
            'services.twilio.sid' => null,
            'services.twilio.token' => null,
            'services.twilio.from' => null,
        ]);

        $user = User::factory()->create(['two_factor_method' => 'sms', 'phone_number' => '77778888']);

        app(TwoFactorChallengeService::class)->issueChallenge($user);

        Mail::assertSent(LoginCode::class);
        Http::assertNothingSent();
        $this->assertSame('email', $user->loginCodes()->latest('id')->first()->channel);
    }
}
